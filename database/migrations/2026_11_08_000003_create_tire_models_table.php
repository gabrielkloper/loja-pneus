<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo compartilhado: o mesmo pneu (marca + modelo + medida +
        // índices) existe uma vez só, e vários vendedores anunciam ofertas
        // dele. É o modelo "catálogo + ofertas" do Mercado Livre (ADR-0003).
        Schema::create('tire_models', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 60);
            $table->string('model', 80);

            // Medida 205/55 R16 → largura 205 mm, perfil 55%, aro 16".
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('aspect_ratio');
            $table->decimal('rim', 4, 1); // decimal porque existem aros como 17.5 e 22.5

            $table->unsignedSmallInteger('load_index'); // ex.: 91 = 615 kg por pneu
            $table->string('speed_index', 2);           // ex.: V = até 240 km/h

            $table->timestamps();

            $table->unique(
                ['brand', 'model', 'width', 'aspect_ratio', 'rim', 'load_index', 'speed_index'],
                'tire_models_spec_unique',
            );
            $table->index(['width', 'aspect_ratio', 'rim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tire_models');
    }
};
