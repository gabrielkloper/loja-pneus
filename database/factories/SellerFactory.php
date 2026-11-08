<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\SellerStatus;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factories geram dados falsos para testes e seeders:
 *     Seller::factory()->create()            → loja ativa com entrega
 *     Seller::factory()->pending()->create() → loja aguardando aprovação
 *
 * @extends Factory<Seller>
 */
class SellerFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Pneus '.fake()->unique()->lastName();

        return [
            'owner_id' => User::factory(),
            'trade_name' => $name,
            'legal_name' => $name.' Ltda',
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'document_type' => DocumentType::Cnpj,
            'document' => self::validCnpj(),
            'phone' => '41999990000',
            'status' => SellerStatus::Active,
            'approved_at' => now(),
            'delivery_fee_cents' => 3000,
            'delivery_days' => 3,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => SellerStatus::Pending, 'approved_at' => null]);
    }

    /** Loja que não entrega, só aceita retirada. */
    public function pickupOnly(): static
    {
        return $this->state(['delivery_fee_cents' => null, 'delivery_days' => null]);
    }

    /**
     * Gera um CNPJ numérico com dígitos verificadores corretos.
     * Os documentos gerados são fictícios.
     */
    public static function validCnpj(): string
    {
        $base = str_pad((string) fake()->unique()->numberBetween(10_000_000, 99_999_999), 8, '0', STR_PAD_LEFT).'0001';

        foreach ([[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]] as $weights) {
            $sum = 0;
            foreach ($weights as $i => $weight) {
                $sum += (int) $base[$i] * $weight;
            }
            $remainder = $sum % 11;
            $base .= $remainder < 2 ? '0' : (string) (11 - $remainder);
        }

        return $base;
    }
}
