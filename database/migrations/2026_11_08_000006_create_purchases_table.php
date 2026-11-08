<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compra = um checkout, com UM pagamento, que pode conter pedidos de
        // várias lojas (ADR-0004).
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users');

            // Chave de idempotência (ADR-0008): repetir o mesmo checkout com
            // a mesma chave devolve a mesma compra em vez de criar outra.
            $table->string('idempotency_key', 64);

            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('total_cents');

            $table->string('payment_provider', 20);
            $table->string('payment_reference', 80)->nullable()->unique(); // id da cobrança no gateway
            $table->text('pix_code')->nullable();                          // "Pix copia e cola"

            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['buyer_id', 'idempotency_key']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
