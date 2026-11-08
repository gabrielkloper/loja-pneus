<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Endereço de entrega salvo pelo comprador.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $label
 * @property string $recipient_name
 * @property string $zip_code
 * @property string $street
 * @property string $number
 * @property string|null $complement
 * @property string $district
 * @property string $city
 * @property string $state
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable([
    'label', 'recipient_name', 'zip_code', 'street', 'number', 'complement', 'district', 'city', 'state',
])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function line(): string
    {
        $street = trim("{$this->street}, {$this->number}".($this->complement ? " ({$this->complement})" : ''));

        return "{$street} — {$this->district}, {$this->city}/{$this->state}, CEP {$this->zip_code}";
    }

    /**
     * Cópia do endereço guardada dentro do pedido no momento da compra.
     *
     * @return array<string, string|null>
     */
    public function snapshot(): array
    {
        return $this->only([
            'recipient_name', 'zip_code', 'street', 'number', 'complement', 'district', 'city', 'state',
        ]);
    }
}
