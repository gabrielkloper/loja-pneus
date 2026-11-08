<?php

namespace App\Console\Commands;

use App\Actions\Sellers\ApproveSeller;
use App\Models\Seller;
use Illuminate\Console\Command;

/**
 * Aprova uma loja pelo terminal:
 *     php artisan marketplace:approve-seller pneus-do-ze
 */
class ApproveSellerCommand extends Command
{
    protected $signature = 'marketplace:approve-seller {slug : Slug da loja (aparece na URL /lojas/{slug})}';

    protected $description = 'Aprova uma loja para aparecer na vitrine';

    public function handle(ApproveSeller $approve): int
    {
        $seller = Seller::query()->where('slug', $this->argument('slug'))->first();

        if ($seller === null) {
            $this->error('Loja não encontrada.');

            return self::FAILURE;
        }

        $approve->handle($seller);
        $this->info("Loja {$seller->trade_name} aprovada.");

        return self::SUCCESS;
    }
}
