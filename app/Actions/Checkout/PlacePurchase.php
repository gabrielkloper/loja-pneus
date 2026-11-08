<?php

namespace App\Actions\Checkout;

use App\Data\CheckoutItem;
use App\Data\FulfillmentChoice;
use App\Enums\FulfillmentMethod;
use App\Enums\OrderEventActor;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Address;
use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\User;
use App\Payments\PaymentGateway;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Fecha uma compra: transforma o carrinho em uma Purchase com um Order por
 * loja, baixa o estoque e cria a cobrança no gateway (ADR-0004 e ADR-0008).
 *
 * É a ÚNICA forma de criar pedidos no sistema. A tela do carrinho chama
 * esta classe hoje; a API para agentes chamará a mesma classe amanhã.
 */
final class PlacePurchase
{
    // O container injeta o gateway configurado (FakeGateway hoje).
    public function __construct(private readonly PaymentGateway $gateway) {}

    /**
     * @param  list<CheckoutItem>  $items
     * @param  array<int, FulfillmentChoice>  $fulfillment  id da loja => como receber
     * @param  string  $idempotencyKey  identificador único desta tentativa de compra
     *
     * @throws BusinessRuleException quando alguma regra impede a compra
     */
    public function handle(User $buyer, array $items, array $fulfillment, string $idempotencyKey): Purchase
    {
        // 1. Idempotência: se esta chave já gerou uma compra, devolve a mesma.
        //    Protege contra clique duplo e contra agentes que repetem a chamada.
        if ($existing = $this->findExisting($buyer, $idempotencyKey)) {
            return $existing;
        }

        $quantities = $this->mergeQuantities($items);

        if ($quantities === []) {
            throw new BusinessRuleException('Seu carrinho está vazio.');
        }

        // 2. Tudo que mexe no banco acontece numa transação: se qualquer
        //    passo falhar (ex.: estoque de um item acabou), NADA é gravado
        //    e o estoque já baixado dos outros itens volta automaticamente.
        try {
            $purchase = DB::transaction(
                fn () => $this->createPurchase($buyer, $quantities, $fulfillment, $idempotencyKey),
            );
        } catch (UniqueConstraintViolationException $e) {
            // Duas requisições com a mesma chave chegaram juntas e a outra
            // gravou primeiro. A nossa foi desfeita; devolvemos a dela.
            return $this->findExisting($buyer, $idempotencyKey) ?? throw $e;
        }

        // 3. A cobrança é criada FORA da transação: chamadas de rede podem
        //    demorar e não devem segurar travas do banco. Se o gateway falhar,
        //    a compra fica sem cobrança e expira sozinha, devolvendo o estoque.
        $charge = $this->gateway->createCharge($purchase);

        $purchase->forceFill([
            'payment_reference' => $charge->reference,
            'pix_code' => $charge->pixCode,
        ])->save();

        return $purchase;
    }

    private function findExisting(User $buyer, string $idempotencyKey): ?Purchase
    {
        return Purchase::query()
            ->where('buyer_id', $buyer->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    /**
     * Junta linhas repetidas do mesmo anúncio e valida as quantidades.
     *
     * @param  list<CheckoutItem>  $items
     * @return array<int, int> id do anúncio => quantidade
     */
    private function mergeQuantities(array $items): array
    {
        $quantities = [];

        foreach ($items as $item) {
            if ($item->quantity < 1) {
                throw new BusinessRuleException('A quantidade de cada item deve ser pelo menos 1.');
            }
            $quantities[$item->listingId] = ($quantities[$item->listingId] ?? 0) + $item->quantity;
        }

        $max = (int) config('marketplace.max_units_per_item');

        foreach ($quantities as $quantity) {
            if ($quantity > $max) {
                throw new BusinessRuleException("O limite é de {$max} unidades por anúncio em cada compra.");
            }
        }

        return $quantities;
    }

    /**
     * @param  array<int, int>  $quantities
     * @param  array<int, FulfillmentChoice>  $fulfillment
     */
    private function createPurchase(User $buyer, array $quantities, array $fulfillment, string $idempotencyKey): Purchase
    {
        $listings = $this->loadAndCheckListings($buyer, $quantities);

        // A compra é gravada PRIMEIRO: se a chave de idempotência já existir,
        // o índice único dispara aqui, antes de mexermos em qualquer estoque.
        $purchase = Purchase::forceCreate([
            'buyer_id' => $buyer->id,
            'idempotency_key' => $idempotencyKey,
            'status' => PurchaseStatus::Pending,
            'total_cents' => 0, // calculado abaixo
            'payment_provider' => $this->gateway->name(),
            'expires_at' => now()->addMinutes((int) config('marketplace.payment_window_minutes')),
        ]);

        $total = 0;

        // Um pedido por loja, como no Mercado Livre e na Amazon.
        foreach ($listings->groupBy('seller_id')->sortKeys() as $sellerId => $sellerListings) {
            /** @var Collection<int, Listing> $sellerListings */
            $seller = $sellerListings->first()->seller;
            $choice = $fulfillment[$sellerId] ?? throw new BusinessRuleException(
                "Escolha entrega ou retirada para os itens da loja {$seller->trade_name}.",
            );

            $order = $this->createOrder($purchase, $buyer, $seller, $sellerListings, $quantities, $choice);
            $total += $order->total_cents;
        }

        $purchase->forceFill(['total_cents' => $total])->save();

        return $purchase;
    }

    /**
     * Carrega os anúncios do banco e confere se todos podem ser comprados.
     *
     * @param  array<int, int>  $quantities
     * @return Collection<int, Listing>
     */
    private function loadAndCheckListings(User $buyer, array $quantities): Collection
    {
        $listings = Listing::query()
            ->with(['seller', 'tireModel', 'location'])
            ->whereKey(array_keys($quantities))
            ->get()
            ->keyBy('id');

        foreach (array_keys($quantities) as $listingId) {
            $listing = $listings->get($listingId);

            if ($listing === null || ! $listing->isVisible()) {
                throw new BusinessRuleException("O anúncio #{$listingId} não está mais disponível.");
            }

            if ($listing->seller->owner_id === $buyer->id) {
                throw new BusinessRuleException('Você não pode comprar anúncios da sua própria loja.');
            }
        }

        return $listings;
    }

    /**
     * @param  Collection<int, Listing>  $listings
     * @param  array<int, int>  $quantities
     */
    private function createOrder(
        Purchase $purchase,
        User $buyer,
        Seller $seller,
        Collection $listings,
        array $quantities,
        FulfillmentChoice $choice,
    ): Order {
        [$address, $pickupLocation] = $this->resolveFulfillment($buyer, $seller, $listings, $choice);

        $subtotal = 0;
        $itemRows = [];

        foreach ($listings as $listing) {
            $quantity = $quantities[$listing->id];

            // Baixa de estoque ATÔMICA: um único UPDATE com a condição
            // "stock >= quantidade". Se duas pessoas compram a última unidade
            // ao mesmo tempo, o banco deixa só uma das atualizações passar;
            // a outra afeta 0 linhas e cai no erro abaixo. Nunca vendemos
            // mais do que existe.
            $updated = Listing::query()
                ->whereKey($listing->id)
                ->where('stock', '>=', $quantity)
                ->decrement('stock', $quantity);

            if ($updated === 0) {
                throw new BusinessRuleException("Estoque insuficiente para {$listing->title()}.");
            }

            // Preço vem do banco, nunca de quem chamou.
            $lineTotal = $listing->price_cents * $quantity;
            $subtotal += $lineTotal;

            $itemRows[] = [
                'listing_id' => $listing->id,
                'title' => $listing->title(),
                'unit_price_cents' => $listing->price_cents,
                'quantity' => $quantity,
                'total_cents' => $lineTotal,
            ];
        }

        $shipping = $choice->method === FulfillmentMethod::Delivery ? (int) $seller->delivery_fee_cents : 0;

        $order = Order::forceCreate([
            'purchase_id' => $purchase->id,
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'status' => OrderStatus::AwaitingPayment,
            'fulfillment_method' => $choice->method,
            'pickup_location_id' => $pickupLocation?->id,
            'shipping_address' => $address?->snapshot(),
            'subtotal_cents' => $subtotal,
            'shipping_cents' => $shipping,
            'total_cents' => $subtotal + $shipping,
        ]);

        foreach ($itemRows as $row) {
            OrderItem::forceCreate($row + ['order_id' => $order->id]);
        }

        $order->recordEvent(
            OrderEventActor::System,
            'Pedido criado. Aguardando a confirmação do pagamento.',
            OrderStatus::AwaitingPayment,
        );

        return $order;
    }

    /**
     * Confere a escolha de entrega/retirada para uma loja.
     *
     * @param  Collection<int, Listing>  $listings
     * @return array{0: Address|null, 1: SellerLocation|null}
     */
    private function resolveFulfillment(User $buyer, Seller $seller, Collection $listings, FulfillmentChoice $choice): array
    {
        if ($choice->method === FulfillmentMethod::Delivery) {
            if (! $seller->offersDelivery()) {
                throw new BusinessRuleException("A loja {$seller->trade_name} não faz entregas; escolha retirada.");
            }

            // O endereço precisa ser DO comprador. Sem esta checagem, alguém
            // poderia mandar o id do endereço de outra pessoa.
            $address = $buyer->addresses()->whereKey($choice->addressId)->first()
                ?? throw new BusinessRuleException('Escolha um dos seus endereços de entrega.');

            return [$address, null];
        }

        // Retirada: todos os itens da loja precisam estar no mesmo local.
        $locationIds = $listings->pluck('seller_location_id')->unique();

        if ($locationIds->count() > 1) {
            throw new BusinessRuleException(
                "Os itens da loja {$seller->trade_name} estão em locais diferentes. Escolha entrega ou compre em pedidos separados.",
            );
        }

        $location = $listings->first()?->location;

        if ($location === null || ! $location->pickup_enabled) {
            throw new BusinessRuleException("A loja {$seller->trade_name} não oferece retirada neste local.");
        }

        return [null, $location];
    }
}
