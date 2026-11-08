<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\SellerStatus;
use Carbon\CarbonInterface;
use Database\Factories\SellerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma loja do marketplace (ADR-0003).
 *
 * @property int $id
 * @property int $owner_id
 * @property string $trade_name
 * @property string $legal_name
 * @property string $slug
 * @property DocumentType $document_type
 * @property string $document
 * @property string $phone
 * @property SellerStatus $status
 * @property CarbonInterface|null $approved_at
 * @property int|null $delivery_fee_cents
 * @property int|null $delivery_days
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User $owner
 */
// "status" e "approved_at" ficam FORA do Fillable de propósito: nenhum
// formulário pode aprovar a própria loja por atribuição em massa.
#[Fillable([
    'trade_name', 'legal_name', 'slug', 'document_type', 'document', 'phone',
    'delivery_fee_cents', 'delivery_days',
])]
class Seller extends Model
{
    /** @use HasFactory<SellerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'status' => SellerStatus::class,
            'approved_at' => 'datetime',
            'delivery_fee_cents' => 'integer',
            'delivery_days' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<SellerLocation, $this> */
    public function locations(): HasMany
    {
        return $this->hasMany(SellerLocation::class);
    }

    /** @return HasMany<Listing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Filtro reutilizável: Seller::query()->active()->get().
     *
     * @param  Builder<Seller>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', SellerStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === SellerStatus::Active;
    }

    /** A loja entrega quando definiu uma taxa de entrega (mesmo que zero). */
    public function offersDelivery(): bool
    {
        return $this->delivery_fee_cents !== null;
    }

    /** CPF 123.456.789-09 ou CNPJ 12.345.678/0001-95, para exibição. */
    public function formattedDocument(): string
    {
        $d = $this->document;

        return $this->document_type === DocumentType::Cpf
            ? sprintf('%s.%s.%s-%s', substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 3), substr($d, 9, 2))
            : sprintf('%s.%s.%s/%s-%s', substr($d, 0, 2), substr($d, 2, 3), substr($d, 5, 3), substr($d, 8, 4), substr($d, 12, 2));
    }
}
