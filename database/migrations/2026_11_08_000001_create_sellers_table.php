<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();

            // Dono da loja. Uma conta tem no máximo uma loja neste incremento;
            // equipes com vários usuários ficam para uma ADR futura.
            $table->foreignId('owner_id')->unique()->constrained('users');

            $table->string('trade_name', 120);   // nome fantasia, exibido na vitrine
            $table->string('legal_name', 160);   // razão social ou nome completo
            $table->string('slug', 140)->unique(); // usado na URL /lojas/{slug}

            $table->string('document_type', 4);   // 'cpf' ou 'cnpj'
            $table->string('document', 14)->unique(); // só dígitos
            $table->string('phone', 20);

            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('approved_at')->nullable();

            // Entrega feita pelo próprio vendedor (ADR-0005). Taxa nula = a
            // loja não entrega, só aceita retirada.
            $table->unsignedInteger('delivery_fee_cents')->nullable();
            $table->unsignedSmallInteger('delivery_days')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
