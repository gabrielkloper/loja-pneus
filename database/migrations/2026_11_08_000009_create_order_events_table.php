<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Linha do tempo do pedido (ADR-0005). Cada mudança de situação,
        // previsão ou recado vira uma linha; nada é sobrescrito, o que dá um
        // histórico auditável para comprador, vendedor e suporte.
        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('status', 20)->nullable(); // situação após o evento, se mudou
            $table->string('actor', 10);              // system, buyer ou seller
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('message', 500)->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
    }
};
