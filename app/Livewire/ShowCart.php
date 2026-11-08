<?php

namespace App\Livewire;

use App\Actions\Checkout\PlacePurchase;
use App\Data\CheckoutItem;
use App\Data\FulfillmentChoice;
use App\Enums\FulfillmentMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\Listing;
use App\Models\User;
use App\Support\AddressRules;
use App\Support\BrazilianStates;
use App\Support\Cart;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Carrinho e fechamento da compra.
 *
 * Os itens ficam agrupados por loja, porque cada loja vira um pedido
 * separado (ADR-0004), com sua própria escolha de entrega ou retirada.
 */
#[Layout('layouts::shop')]
#[Title('Carrinho')]
class ShowCart extends Component
{
    /**
     * Chave de idempotência desta tentativa de compra (ADR-0008).
     * #[Locked] impede que o navegador altere o valor.
     */
    #[Locked]
    public string $checkoutKey = '';

    /** @var array<int|string, string> id da loja => 'delivery' ou 'pickup' */
    public array $fulfillment = [];

    /** Endereço usado para todos os pedidos com entrega. */
    public ?int $addressId = null;

    public bool $showAddressForm = false;

    /** @var array<string, string> */
    public array $newAddress = [
        'label' => '', 'recipient_name' => '', 'zip_code' => '', 'street' => '', 'number' => '',
        'complement' => '', 'district' => '', 'city' => '', 'state' => '',
    ];

    public function mount(): void
    {
        $this->checkoutKey = (string) Str::uuid();

        $user = Auth::user();
        if ($user instanceof User) {
            $this->addressId = $user->addresses()->latest('id')->value('id');
            $this->showAddressForm = $this->addressId === null;
            $this->newAddress['recipient_name'] = $user->name;
        }
    }

    public function updateQuantity(int $listingId, int $quantity): void
    {
        $this->cart()->set($listingId, $quantity);
    }

    public function remove(int $listingId): void
    {
        $this->cart()->remove($listingId);
    }

    public function saveAddress(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            $this->redirectRoute('login');

            return;
        }

        $rules = ['newAddress.label' => ['nullable', 'string', 'max:40'], 'newAddress.recipient_name' => ['required', 'string', 'max:120']]
            + AddressRules::rules('newAddress.');

        $data = $this->validate($rules)['newAddress'];
        $data['zip_code'] = AddressRules::normalizeZip($data['zip_code']);

        $address = $user->addresses()->create($data);

        $this->addressId = $address->id;
        $this->showAddressForm = false;
    }

    /**
     * Botão "Finalizar compra". Toda regra de verdade está em PlacePurchase;
     * aqui só traduzimos a tela para os objetos que a Action espera.
     */
    public function placeOrder(): void
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            $this->redirectRoute('login');

            return;
        }

        $cart = $this->cart();

        if (! $user->hasVerifiedEmail()) {
            $this->addError('checkout', 'Confirme seu e-mail antes de comprar.');

            return;
        }

        // Limite de tentativas por minuto: atrapalha bots que tentam
        // travar estoque abrindo compras em série (ADR-0008).
        $rateKey = 'checkout:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateKey, (int) config('marketplace.checkout_attempts_per_minute'))) {
            $this->addError('checkout', 'Muitas tentativas. Aguarde um minuto e tente de novo.');

            return;
        }
        RateLimiter::hit($rateKey, 60);

        $items = [];
        foreach ($cart->items() as $listingId => $quantity) {
            $items[] = new CheckoutItem((int) $listingId, (int) $quantity);
        }

        $choices = [];
        foreach ($this->fulfillment as $sellerId => $method) {
            $choices[(int) $sellerId] = $method === FulfillmentMethod::Pickup->value
                ? FulfillmentChoice::pickup()
                : FulfillmentChoice::delivery((int) $this->addressId);
        }

        try {
            $purchase = app(PlacePurchase::class)->handle($user, $items, $choices, $this->checkoutKey);
        } catch (BusinessRuleException $e) {
            $this->addError('checkout', $e->getMessage());

            return;
        }

        $cart->clear();

        $this->redirectRoute('purchases.show', ['purchase' => $purchase->id]);
    }

    public function render(): View
    {
        $quantities = $this->cart()->items();

        /** @var Collection<int, Listing> $listings */
        $listings = Listing::query()
            ->with(['tireModel', 'seller', 'location'])
            ->whereKey(array_keys($quantities))
            ->get();

        // Monta os grupos por loja com subtotais calculados AQUI, no servidor.
        $groups = $listings->groupBy('seller_id')->map(function (Collection $sellerListings, int $sellerId) use ($quantities) {
            $seller = $sellerListings->first()->seller;

            // Escolha padrão: entrega se a loja entrega; senão, retirada.
            if (! isset($this->fulfillment[$sellerId])) {
                $this->fulfillment[$sellerId] = $seller->offersDelivery()
                    ? FulfillmentMethod::Delivery->value
                    : FulfillmentMethod::Pickup->value;
            }

            $subtotal = $sellerListings->sum(fn (Listing $l) => $l->price_cents * ($quantities[$l->id] ?? 0));
            $method = $this->fulfillment[$sellerId];
            $shipping = $method === FulfillmentMethod::Delivery->value ? (int) $seller->delivery_fee_cents : 0;

            return [
                'seller' => $seller,
                'listings' => $sellerListings,
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'total' => $subtotal + $shipping,
                'pickupLocation' => $sellerListings->first()->location,
            ];
        });

        $user = Auth::user();

        return view('livewire.show-cart', [
            'groups' => $groups,
            'quantities' => $quantities,
            'grandTotal' => $groups->sum('total'),
            'addresses' => $user instanceof User ? $user->addresses()->latest('id')->get() : collect(),
            'needsAddress' => $groups->contains(fn (array $g) => $this->fulfillment[$g['seller']->id] === FulfillmentMethod::Delivery->value),
            'states' => BrazilianStates::all(),
            'maxUnits' => (int) config('marketplace.max_units_per_item'),
        ]);
    }

    private function cart(): Cart
    {
        return app(Cart::class);
    }
}
