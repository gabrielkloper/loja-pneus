<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Notificação recebida do gateway de pagamento, guardada para garantir
 * processamento único e para auditoria.
 *
 * @property int $id
 * @property string $provider
 * @property string $event_id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property CarbonInterface|null $processed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
#[Fillable(['provider', 'event_id', 'type', 'payload'])]
class PaymentWebhookEvent extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
