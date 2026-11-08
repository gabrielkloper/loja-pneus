<?php

namespace Database\Seeders;

use App\Enums\TireCondition;
use App\Models\Address;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\TireModel;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Dados de demonstração: php artisan migrate:fresh --seed
 *
 * Contas criadas (senha "password" para todas):
 *   comprador@exemplo.com  → cliente com endereço salvo
 *   vendedor@exemplo.com   → dono da loja "Pneus Curitiba"
 *   remold@exemplo.com     → dono da loja "Remold Sul" (só retirada)
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $buyer = User::factory()->create(['name' => 'Ana Compradora', 'email' => 'comprador@exemplo.com']);
        Address::factory()->for($buyer)->create(['recipient_name' => $buyer->name]);

        $catalog = $this->catalog();

        // Loja 1: entrega própria, dois endereços (Curitiba e Joinville).
        $curitiba = Seller::factory()
            ->for(User::factory()->state(['name' => 'Bruno Vendedor', 'email' => 'vendedor@exemplo.com']), 'owner')
            ->create(['trade_name' => 'Pneus Curitiba', 'legal_name' => 'Pneus Curitiba Ltda', 'slug' => 'pneus-curitiba']);

        $cwbMatriz = SellerLocation::factory()->for($curitiba)->create(['name' => 'Matriz']);
        $joinville = SellerLocation::factory()->for($curitiba)->create([
            'name' => 'Filial Joinville', 'city' => 'Joinville', 'state' => 'SC', 'zip_code' => '89201000',
            'street' => 'Rua do Príncipe', 'district' => 'Centro', 'latitude' => '-26.3044084', 'longitude' => '-48.8463832',
        ]);

        // Loja 2: remold, só retirada.
        $remold = Seller::factory()
            ->pickupOnly()
            ->for(User::factory()->state(['name' => 'Carla Remold', 'email' => 'remold@exemplo.com']), 'owner')
            ->create(['trade_name' => 'Remold Sul', 'legal_name' => 'Remold Sul Recapagens Ltda', 'slug' => 'remold-sul']);

        $portoAlegre = SellerLocation::factory()->for($remold)->create([
            'name' => 'Fábrica', 'city' => 'Porto Alegre', 'state' => 'RS', 'zip_code' => '90010000',
            'street' => 'Avenida Borges de Medeiros', 'district' => 'Centro Histórico',
            'latitude' => '-30.0318', 'longitude' => '-51.2295', 'opening_hours' => 'Seg a sáb, 7h às 17h',
        ]);

        // Loja 3: aguardando aprovação (não aparece na vitrine).
        Seller::factory()->pending()->create(['trade_name' => 'Loja Nova', 'slug' => 'loja-nova']);

        foreach ($catalog as $i => $tire) {
            Listing::factory()->at($i % 2 === 0 ? $cwbMatriz : $joinville)->create([
                'tire_model_id' => $tire->id,
                'price_cents' => 32990 + $i * 4500,
                'stock' => 8 + $i,
            ]);
        }

        // O mesmo pneu em remold, para comparar ofertas na página do anúncio.
        foreach ($catalog->take(3) as $tire) {
            Listing::factory()->remold()->at($portoAlegre)->create([
                'tire_model_id' => $tire->id,
                'condition' => TireCondition::Remold,
            ]);
        }
    }

    /**
     * @return Collection<int, TireModel>
     */
    private function catalog(): Collection
    {
        return collect([
            ['Pirelli', 'Cinturato P7', 205, 55, '16.0', 91, 'V'],
            ['Michelin', 'Primacy 4', 205, 55, '16.0', 91, 'V'],
            ['Goodyear', 'EfficientGrip Performance', 195, 65, '15.0', 91, 'H'],
            ['Bridgestone', 'Turanza T005', 225, 45, '17.0', 94, 'W'],
            ['Continental', 'PowerContact 2', 175, 70, '14.0', 84, 'T'],
            ['Pirelli', 'Scorpion ATR', 265, 70, '16.0', 112, 'T'],
            ['Michelin', 'X Multi Z', 295, 80, '22.5', 152, 'M'],
        ])->map(fn (array $t) => TireModel::create(array_combine(
            ['brand', 'model', 'width', 'aspect_ratio', 'rim', 'load_index', 'speed_index'],
            $t,
        )));
    }
}
