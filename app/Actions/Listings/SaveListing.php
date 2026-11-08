<?php

namespace App\Actions\Listings;

use App\Enums\ListingStatus;
use App\Enums\TireCondition;
use App\Exceptions\BusinessRuleException;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\TireModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cria ou atualiza um anúncio.
 *
 * O vendedor digita a especificação do pneu; esta classe procura o pneu
 * no catálogo e só cria um novo se ainda não existir (ADR-0003). Assim,
 * dez lojas vendendo o mesmo Pirelli 205/55 R16 91V apontam para o mesmo
 * registro, e a vitrine pode comparar ofertas.
 */
final class SaveListing
{
    /**
     * @param  array{brand: string, model: string, width: int, aspect_ratio: int, rim: string, load_index: int, speed_index: string, condition: string, status: string, price_cents: int, stock: int, seller_location_id: int, dot_code?: string|null, inmetro_registration?: string|null, warranty_months?: int|null, description?: string|null}  $data
     */
    public function handle(Seller $seller, array $data, ?Listing $listing = null): Listing
    {
        if ($listing !== null && $listing->seller_id !== $seller->id) {
            throw new BusinessRuleException('Este anúncio não pertence à sua loja.');
        }

        // O local precisa ser desta loja; sem isso, um vendedor poderia
        // "estocar" pneus no endereço de outra loja.
        if (! $seller->locations()->whereKey($data['seller_location_id'])->exists()) {
            throw new BusinessRuleException('Escolha um dos endereços da sua loja.');
        }

        $condition = TireCondition::from($data['condition']);

        if ($condition === TireCondition::Remold && blank($data['inmetro_registration'] ?? null)) {
            throw new BusinessRuleException('Pneus remold exigem o número de registro no Inmetro.');
        }

        return DB::transaction(function () use ($seller, $data, $listing, $condition): Listing {
            $tireModel = TireModel::firstOrCreate([
                // Normalizamos para que "pirelli" e "Pirelli " virem o mesmo pneu
                // (ucfirst, e não title case, para preservar "BFGoodrich").
                'brand' => Str::ucfirst(Str::squish($data['brand'])),
                'model' => trim($data['model']),
                'width' => $data['width'],
                'aspect_ratio' => $data['aspect_ratio'],
                'rim' => number_format((float) $data['rim'], 1, '.', ''),
                'load_index' => $data['load_index'],
                'speed_index' => strtoupper(trim($data['speed_index'])),
            ]);

            $listing ??= new Listing;
            $listing->seller_id = $seller->id;

            $listing->fill([
                'tire_model_id' => $tireModel->id,
                'seller_location_id' => $data['seller_location_id'],
                'condition' => $condition,
                'status' => ListingStatus::from($data['status']),
                'price_cents' => $data['price_cents'],
                'stock' => $data['stock'],
                'dot_code' => $data['dot_code'] ?? null,
                'inmetro_registration' => $condition === TireCondition::Remold ? ($data['inmetro_registration'] ?? null) : null,
                'warranty_months' => $data['warranty_months'] ?? 0,
                'description' => $data['description'] ?? null,
            ]);

            $listing->save();

            return $listing;
        });
    }
}
