<?php

namespace App\Actions\Checkout;

use App\Enums\OrderEventActor;
use App\Enums\OrderStatus;
use App\Enums\PurchaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Listing;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Cancela uma compra que AINDA NÃO FOI PAGA e devolve o estoque.
 *
 * Usada em dois casos:
 *   - o comprador desiste antes de pagar  → status Cancelled
 *   - o prazo de pagamento acabou         → status Expired (comando agendado)
 *
 * Antes do pagamento a compra é cancelada inteira, nunca um pedido só:
 * o Pix gerado é do valor total, e cancelar uma parte deixaria a cobrança
 * com o valor errado.
 */
final class CancelPurchase
{
    public function handle(Purchase $purchase, PurchaseStatus $as, ?User $buyer = null): void
    {
        if (! in_array($as, [PurchaseStatus::Cancelled, PurchaseStatus::Expired], true)) {
            throw new \InvalidArgumentException('Use Cancelled ou Expired.');
        }

        DB::transaction(function () use ($purchase, $as, $buyer): void {
            $purchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($purchase->status !== PurchaseStatus::Pending) {
                throw new BusinessRuleException('Só é possível cancelar compras que ainda aguardam pagamento.');
            }

            $purchase->forceFill(['status' => $as])->save();

            $reason = $as === PurchaseStatus::Expired
                ? 'Pagamento não confirmado dentro do prazo.'
                : 'Compra cancelada pelo comprador antes do pagamento.';

            foreach ($purchase->orders()->with('items')->get() as $order) {
                // Devolve cada unidade ao estoque do anúncio.
                foreach ($order->items as $item) {
                    Listing::query()->whereKey($item->listing_id)->increment('stock', $item->quantity);
                }

                $order->forceFill([
                    'status' => OrderStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ])->save();

                $order->recordEvent(
                    $buyer ? OrderEventActor::Buyer : OrderEventActor::System,
                    $reason,
                    OrderStatus::Cancelled,
                    $buyer,
                );
            }
        });
    }
}
