<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Endereços de entrega salvos pelo comprador.
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40)->nullable(); // ex.: "Casa", "Oficina"
            $table->string('recipient_name', 120);
            $table->string('zip_code', 8);
            $table->string('street', 160);
            $table->string('number', 20);
            $table->string('complement', 80)->nullable();
            $table->string('district', 80);
            $table->string('city', 80);
            $table->char('state', 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
