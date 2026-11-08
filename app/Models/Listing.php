<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\SellerStatus;
use App\Enums\TireCondition;
use Carbon\CarbonInterface;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Anúncio: a oferta de um vendedor para um pneu do catálogo.
 *
 * @property int $id
 * @property int $seller_id
 * @property int $tire_model_id
 * @property int $seller_location_id
 * @property TireCondition $condition
 * @property ListingStatus $status
 * @property int $price_cents
 * @property int $stock
 * @property string|null $dot_code
 * @property string|null $inmetro_registration
 * @property int $warranty_months
 * @property string|null $description
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Seller $seller
 * @property-read TireModel $tireModel
 * @property-read SellerLocation $location
 */
#[Fillable([
    'tire_model_id', 'seller_location_id', 'condition', 'status', 'price_cents', 'stock',
    'dot_code', 'inmetro_registration', 'warranty_months', 'description',
])]
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition' => TireCondition::class,
            'status' => ListingStatus::class,
            'price_cents' => 'integer',
            'stock' => 'integer',
            'warranty_months' => 'integer',
        ];
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return BelongsTo<TireModel, $this> */
    public function tireModel(): BelongsTo
    {
        return $this->belongsTo(TireModel::class);
    }

    /** @return BelongsTo<SellerLocation, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(SellerLocation::class, 'seller_location_id');
    }

    /**
     * O que aparece na vitrine: anúncio ativo, com estoque, de loja ativa.
     * Concentrar essa regra num lugar só evita que uma tela esqueça um
     * dos três filtros.
     *
     * @param  Builder<Listing>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', ListingStatus::Active)
            ->where('stock', '>', 0)
            ->whereHas('seller', fn (Builder $seller) => $seller->where('status', SellerStatus::Active));
    }

    public function isVisible(): bool
    {
        return $this->status === ListingStatus::Active
            && $this->stock > 0
            && $this->seller->isActive();
    }

    /** "Pirelli Cinturato P7 205/55 R16 91V — Novo" */
    public function title(): string
    {
        return $this->tireModel->fullName().' — '.$this->condition->label();
    }
}
