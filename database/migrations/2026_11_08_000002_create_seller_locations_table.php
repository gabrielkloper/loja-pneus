<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Endereços físicos da loja: onde os pneus ficam e onde o comprador
        // pode retirar (ADR-0006).
        Schema::create('seller_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->cascadeOnDelete();

            $table->string('name', 80); // ex.: "Matriz", "Filial Centro"
            $table->string('zip_code', 8);
            $table->string('street', 160);
            $table->string('number', 20);
            $table->string('complement', 80)->nullable();
            $table->string('district', 80);
            $table->string('city', 80);
            $table->char('state', 2);

            // Coordenadas opcionais: hoje servem para o link do mapa; no
            // futuro, para a busca por distância.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('pickup_enabled')->default(true);
            $table->string('opening_hours', 255)->nullable();

            $table->timestamps();

            $table->index(['state', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_locations');
    }
};
