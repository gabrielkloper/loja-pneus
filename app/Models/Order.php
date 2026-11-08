<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderEventActor;
use App\Enums\OrderStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Pedido: a parte de uma compra que cabe a UMA loja (ADR-0004).
 *
 * Como em Purchase, não há #[Fillable]: só as Actions alteram pedidos.
 *
 * @property int $id
 * @property int $purchase_id
 * @property int $seller_id
 * @property int $buyer_id
 * @property OrderStatus $status
 * @property FulfillmentMethod $fulfillment_method
 * @property int|null $pickup_location_id
 * @property array<string, string|null>|null $shipping_address
 * @property int $subtotal_cents
 * @property int $shipping_cents
 * @property int $total_cents
 * @property Carbon|null $estimated_delivery_at
 * @property string|null $carrier
 * @property string|null $tracking_code
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property int $refunded_cents
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Purchase $purchase
 * @property-read Seller $seller
 * @property-read User $buyer
 * @property-read SellerLocation|null $pickupLocation
 */
class Order extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'fulfillment_method' => FulfillmentMethod::class,
            'shipping_address' => 'array',
            'subtotal_cents' => 'integer',
            'shipping_cents' => 'integer',
            'total_cents' => 'integer',
            'refunded_cents' => 'integer',
            'estimated_delivery_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Purchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /** @return BelongsTo<SellerLocation, $this> */
    public function pickupLocation(): BelongsTo
    {
        return $this->belongsTo(SellerLocation::class, 'pickup_location_id');
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Linha do tempo, do mais antigo para o mais recente.
     *
     * @return HasMany<OrderEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    /**
     * Acrescenta um evento à linha do tempo. Toda Action que muda o pedido
     * chama este método, então o histórico nunca fica incompleto.
     */
    public function recordEvent(
        OrderEventActor $actor,
        ?string $message = null,
        ?OrderStatus $status = null,
        ?User $user = null,
        ?CarbonInterface $estimatedDeliveryAt = null,
    ): OrderEvent {
        return OrderEvent::forceCreate([
            'order_id' => $this->id,
            'actor' => $actor,
            'actor_user_id' => $user?->id,
            'status' => $status,
            'message' => $message,
            'estimated_delivery_at' => $estimatedDeliveryAt,
        ]);
    }

    /** "P-000123" */
    public function number(): string
    {
        return 'P-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /** Endereço de entrega em uma linha, ou null se for retirada. */
    public function shippingAddressLine(): ?string
    {
        $a = $this->shipping_address;

        if ($a === null) {
            return null;
        }

        $street = trim(($a['street'] ?? '').', '.($a['number'] ?? '').(! empty($a['complement']) ? " ({$a['complement']})" : ''));

        return sprintf(
            '%s — %s — %s, %s/%s, CEP %s',
            $a['recipient_name'] ?? '',
            $street,
            $a['district'] ?? '',
            $a['city'] ?? '',
            $a['state'] ?? '',
            $a['zip_code'] ?? '',
        );
    }
}
