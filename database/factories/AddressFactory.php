<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => 'Casa',
            'recipient_name' => fake()->name(),
            'zip_code' => '01310100',
            'street' => 'Avenida Paulista',
            'number' => (string) fake()->numberBetween(1, 3000),
            'complement' => null,
            'district' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];
    }
}
