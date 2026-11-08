<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Uma compra: um checkout com um único pagamento, contendo um pedido por
 * loja (ADR-0004).
 *
 * Não há #[Fillable] aqui: compras só são criadas pela Action
 * PlacePurchase, que preenche cada campo explicitamente.
 *
 * @property int $id
 * @property int $buyer_id
 * @property string $idempotency_key
 * @property PurchaseStatus $status
 * @property int $total_cents
 * @property string $payment_provider
 * @property string|null $payment_reference
 * @property string|null $pix_code
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $paid_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User $buyer
 */
class Purchase extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseStatus::class,
            'total_cents' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** "C-000042": número amigável para mostrar ao comprador. */
    public function number(): string
    {
        return 'C-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /** Ainda dá para pagar: pendente e dentro do prazo. */
    public function isPayable(): bool
    {
        return $this->status === PurchaseStatus::Pending && $this->expires_at->isFuture();
    }
}
