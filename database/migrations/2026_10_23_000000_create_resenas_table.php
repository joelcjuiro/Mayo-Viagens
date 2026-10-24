<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resenas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->string('nombre', 100);
            $table->string('pais', 100)->nullable();
            $table->string('email', 150);                       // privado: no se muestra en el sitio
            $table->unsignedTinyInteger('calificacion');        // 1 a 5
            $table->text('comentario');
            $table->string('estado', 12)->default('pendiente'); // pendiente | aprobada | rechazada
            $table->text('respuesta')->nullable();              // respuesta pública de la agencia (opcional)
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resenas');
    }
};
