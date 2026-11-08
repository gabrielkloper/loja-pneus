<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pedido = a parte da compra que cabe a UMA loja. É o que o
        // vendedor prepara, envia e atualiza (ADR-0004 e ADR-0005).
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained();
            $table->foreignId('seller_id')->constrained();
            $table->foreignId('buyer_id')->constrained('users');

            $table->string('status', 20)->index();
            $table->string('fulfillment_method', 10);

            // Retirada: onde buscar. Entrega: cópia do endereço no momento da
            // compra (se o comprador editar o endereço depois, o pedido não muda).
            $table->foreignId('pickup_location_id')->nullable()->constrained('seller_locations')->nullOnDelete();
            $table->json('shipping_address')->nullable();

            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('shipping_cents');
            $table->unsignedInteger('total_cents');

            $table->timestamp('estimated_delivery_at')->nullable();
            $table->string('carrier', 60)->nullable();
            $table->string('tracking_code', 80)->nullable();

            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 255)->nullable();
            $table->unsignedInteger('refunded_cents')->default(0);

            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['buyer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
