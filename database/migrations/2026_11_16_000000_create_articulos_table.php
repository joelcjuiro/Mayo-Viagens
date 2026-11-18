<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('articulos')) {
            return;
        }

        Schema::create('articulos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 160);                       // idioma base: portugués
            $table->string('slug', 180)->unique();
            $table->string('categoria', 20)->default('novedad'); // novedad | aviso | guia | consejo
            $table->string('resumen', 320)->nullable();
            $table->longText('contenido');                       // Markdown simple
            $table->string('imagen')->nullable();                // portada (ya optimizada)
            $table->string('imagen_miniatura')->nullable();
            $table->string('imagen_alt', 160)->nullable();
            $table->string('estado', 12)->default('borrador');   // borrador | publicado
            $table->timestamp('publicado_at')->nullable();       // si es futura, queda programado
            $table->boolean('destacado')->default(false);
            $table->unsignedInteger('vistas')->default(0);
            $table->string('meta_descripcion', 200)->nullable();
            $table->json('traducciones')->nullable();            // {"es": {...}, "en": {...}}
            $table->foreignId('autor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['estado', 'publicado_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulos');
    }
};
