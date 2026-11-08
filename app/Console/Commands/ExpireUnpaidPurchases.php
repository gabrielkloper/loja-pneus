<?php

namespace App\Console\Commands;

use App\Actions\Checkout\CancelPurchase;
use App\Enums\PurchaseStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Purchase;
use Illuminate\Console\Command;

/**
 * Expira compras não pagas dentro do prazo e devolve o estoque.
 *
 * Agendado para rodar a cada minuto em routes/console.php. Sem isso, um
 * bot poderia "sequestrar" o estoque fechando compras que nunca paga.
 */
class ExpireUnpaidPurchases extends Command
{
    protected $signature = 'purchases:expire';

    protected $description = 'Expira compras não pagas no prazo e devolve o estoque';

    public function handle(CancelPurchase $cancelPurchase): int
    {
        $count = 0;

        Purchase::query()
            ->where('status', PurchaseStatus::Pending)
            ->where('expires_at', '<', now())
            ->chunkById(100, function ($purchases) use ($cancelPurchase, &$count): void {
                foreach ($purchases as $purchase) {
                    try {
                        $cancelPurchase->handle($purchase, PurchaseStatus::Expired);
                        $count++;
                    } catch (BusinessRuleException) {
                        // Foi paga entre a consulta e o cancelamento: segue em frente.
                    }
                }
            });

        $this->info("{$count} compra(s) expirada(s).");

        return self::SUCCESS;
    }
}
