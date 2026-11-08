<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SellerLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Endereço físico de uma loja: onde o pneu está e onde pode ser retirado.
 *
 * @property int $id
 * @property int $seller_id
 * @property string $name
 * @property string $zip_code
 * @property string $street
 * @property string $number
 * @property string|null $complement
 * @property string $district
 * @property string $city
 * @property string $state
 * @property string|null $latitude
 * @property string|null $longitude
 * @property bool $pickup_enabled
 * @property string|null $opening_hours
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Seller $seller
 */
#[Fillable([
    'name', 'zip_code', 'street', 'number', 'complement', 'district', 'city', 'state',
    'latitude', 'longitude', 'pickup_enabled', 'opening_hours',
])]
class SellerLocation extends Model
{
    /** @use HasFactory<SellerLocationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pickup_enabled' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return HasMany<Listing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** "Rua X, 123 — Centro, Curitiba/PR" */
    public function addressLine(): string
    {
        $street = trim("{$this->street}, {$this->number}".($this->complement ? " ({$this->complement})" : ''));

        return "{$street} — {$this->district}, {$this->city}/{$this->state}";
    }

    /**
     * Link para abrir o local num app de mapas. Usa as coordenadas quando
     * existem (mais preciso) e o endereço escrito como alternativa.
     */
    public function mapUrl(): string
    {
        $query = ($this->latitude !== null && $this->longitude !== null)
            ? "{$this->latitude},{$this->longitude}"
            : $this->addressLine();

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    }
}
