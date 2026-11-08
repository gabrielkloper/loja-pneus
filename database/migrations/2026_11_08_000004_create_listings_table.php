<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anúncio = a oferta de UM vendedor para UM modelo de pneu.
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained();
            $table->foreignId('tire_model_id')->constrained();
            $table->foreignId('seller_location_id')->constrained('seller_locations');

            $table->string('condition', 10);                 // 'new' ou 'remold'
            $table->string('status', 10)->default('active'); // 'active' ou 'paused'

            // Dinheiro sempre em centavos inteiros (ADR-0009).
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('stock');

            $table->string('dot_code', 4)->nullable();             // semana+ano de fabricação, ex.: 2425
            $table->string('inmetro_registration', 40)->nullable(); // obrigatório para remold
            $table->unsignedSmallInteger('warranty_months')->default(0);
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['seller_id', 'status']);
            $table->index(['status', 'price_cents']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
