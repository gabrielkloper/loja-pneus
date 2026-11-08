<?php

namespace App\Livewire\Seller;

use App\Actions\Orders\AdvanceOrderStatus;
use App\Actions\Orders\CancelOrderBySeller;
use App\Actions\Orders\UpdateDeliveryEstimate;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Tela do vendedor para um pedido: ver itens e endereço, avançar a
 * situação, atualizar previsão de entrega e cancelar (ADR-0005).
 */
#[Layout('layouts::shop')]
class ManageOrder extends Component
{
    /**
     * Livewire guarda só o id do model entre requisições e o recarrega a
     * cada ação; o navegador não consegue trocar por outro pedido.
     */
    public Order $order;

    public string $nextStatus = '';

    public string $estimatedDate = '';

    public string $carrier = '';

    public string $trackingCode = '';

    public string $message = '';

    public string $newEstimate = '';

    public string $cancelReason = '';

    public function mount(Order $order): void
    {
        $this->authorize('manage', $order);
        $this->order = $order;

        $options = $order->status->sellerCanMoveTo($order->fulfillment_method);
        $this->nextStatus = $options[0]->value ?? '';
        $this->estimatedDate = $order->estimated_delivery_at?->format('Y-m-d') ?? '';
        $this->carrier = (string) $order->carrier;
        $this->trackingCode = (string) $order->tracking_code;
    }

    public function advance(AdvanceOrderStatus $advance): void
    {
        // Autoriza de novo em CADA ação: a tela pode ter ficado aberta
        // enquanto o acesso mudou.
        $this->authorize('manage', $this->order);

        $this->validate([
            'nextStatus' => ['required'],
            'estimatedDate' => ['nullable', 'date_format:Y-m-d'],
            'carrier' => ['nullable', 'string', 'max:60'],
            'trackingCode' => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $to = OrderStatus::tryFrom($this->nextStatus);
        if ($to === null) {
            $this->addError('nextStatus', 'Escolha a nova situação.');

            return;
        }

        try {
            $advance->handle(
                $this->order,
                $to,
                $this->user(),
                $this->estimatedDate !== '' ? Date::parse($this->estimatedDate)->endOfDay() : null,
                $this->carrier ?: null,
                $this->trackingCode ?: null,
                $this->message ?: null,
            );
        } catch (BusinessRuleException $e) {
            $this->addError('advance', $e->getMessage());

            return;
        }

        $this->order->refresh();
        $this->reset('message');
        $this->nextStatus = $this->order->status->sellerCanMoveTo($this->order->fulfillment_method)[0]->value ?? '';

        Flux::toast(variant: 'success', text: 'Situação atualizada para "'.$this->order->status->label().'".');
    }

    public function updateEstimate(UpdateDeliveryEstimate $update): void
    {
        $this->authorize('manage', $this->order);

        $this->validate([
            'newEstimate' => ['required', 'date_format:Y-m-d'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $update->handle($this->order, Date::parse($this->newEstimate)->endOfDay(), $this->user(), $this->message ?: null);
        } catch (BusinessRuleException $e) {
            $this->addError('newEstimate', $e->getMessage());

            return;
        }

        $this->order->refresh();
        $this->reset('newEstimate', 'message');

        Flux::toast(variant: 'success', text: 'Previsão de entrega atualizada.');
    }

    public function cancel(CancelOrderBySeller $cancel): void
    {
        $this->authorize('manage', $this->order);

        $this->validate(['cancelReason' => ['required', 'string', 'min:5', 'max:255']]);

        try {
            $cancel->handle($this->order, $this->user(), $this->cancelReason);
        } catch (BusinessRuleException $e) {
            $this->addError('cancelReason', $e->getMessage());

            return;
        }

        $this->order->refresh();
        $this->reset('cancelReason');

        Flux::toast(variant: 'warning', text: 'Pedido cancelado e reembolso solicitado.');
    }

    public function render(): View
    {
        $this->order->load(['items', 'events', 'buyer', 'pickupLocation']);

        return view('livewire.seller.manage-order', [
            'nextOptions' => $this->order->status->sellerCanMoveTo($this->order->fulfillment_method),
        ])->title('Pedido '.$this->order->number());
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
