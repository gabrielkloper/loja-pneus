<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Enums\TireCondition;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\TireModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_id' => Seller::factory(),
            'tire_model_id' => TireModel::factory(),
            // O local é criado para a MESMA loja do anúncio.
            'seller_location_id' => fn (array $attributes) => SellerLocation::factory()->create(['seller_id' => $attributes['seller_id']])->id,
            'condition' => TireCondition::New,
            'status' => ListingStatus::Active,
            'price_cents' => 45990,
            'stock' => 10,
            'dot_code' => '2425',
            'inmetro_registration' => null,
            'warranty_months' => 60,
            'description' => null,
        ];
    }

    public function remold(): static
    {
        return $this->state([
            'condition' => TireCondition::Remold,
            'inmetro_registration' => '001234/2024',
            'price_cents' => 24990,
            'warranty_months' => 12,
        ]);
    }

    public function paused(): static
    {
        return $this->state(['status' => ListingStatus::Paused]);
    }

    /** Anúncio no local indicado (e da loja dona desse local). */
    public function at(SellerLocation $location): static
    {
        return $this->state([
            'seller_id' => $location->seller_id,
            'seller_location_id' => $location->id,
        ]);
    }
}
