<?php

namespace Database\Factories;

use App\Models\TireModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TireModel>
 */
class TireModelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand' => fake()->randomElement(['Pirelli', 'Michelin', 'Goodyear', 'Bridgestone', 'Continental']),
            'model' => fake()->unique()->bothify('Modelo ##??'),
            'width' => 205,
            'aspect_ratio' => 55,
            'rim' => '16.0',
            'load_index' => 91,
            'speed_index' => 'V',
        ];
    }
}
