<?php

namespace Database\Factories;

use App\Models\Seller;
use App\Models\SellerLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SellerLocation>
 */
class SellerLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_id' => Seller::factory(),
            'name' => 'Matriz',
            'zip_code' => '80010000',
            'street' => 'Rua XV de Novembro',
            'number' => (string) fake()->numberBetween(1, 2000),
            'complement' => null,
            'district' => 'Centro',
            'city' => 'Curitiba',
            'state' => 'PR',
            'latitude' => '-25.4295963',
            'longitude' => '-49.2712724',
            'pickup_enabled' => true,
            'opening_hours' => 'Seg a sex, 8h às 18h',
        ];
    }

    public function withoutPickup(): static
    {
        return $this->state(['pickup_enabled' => false]);
    }
}
