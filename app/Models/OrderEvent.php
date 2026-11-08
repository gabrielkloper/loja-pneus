<?php

namespace App\Models;

use App\Enums\OrderEventActor;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Um acontecimento na linha do tempo do pedido. Eventos nunca são
 * editados: a linha do tempo só cresce.
 *
 * @property int $id
 * @property int $order_id
 * @property OrderStatus|null $status
 * @property OrderEventActor $actor
 * @property int|null $actor_user_id
 * @property string|null $message
 * @property Carbon|null $estimated_delivery_at
 * @property Carbon $created_at
 */
class OrderEvent extends Model
{
    // A tabela só tem created_at; avisamos o Eloquent para não procurar updated_at.
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'actor' => OrderEventActor::class,
            'estimated_delivery_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
