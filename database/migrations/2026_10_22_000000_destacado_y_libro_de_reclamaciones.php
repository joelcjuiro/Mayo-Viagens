<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->boolean('destacado')->default(false); // "Experiencia destacada" del inicio
        });

        Schema::create('reclamos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();            // LR-2026-00001
            $table->string('tipo', 10);                    // reclamo | queja
            $table->string('nombre', 150);
            $table->string('documento_tipo', 20);          // DNI, CE, Pasaporte...
            $table->string('documento_numero', 30);
            $table->string('domicilio', 200);
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150);
            $table->boolean('menor_edad')->default(false);
            $table->string('apoderado', 150)->nullable();  // padre/madre/tutor si es menor
            $table->string('bien', 10);                    // producto | servicio
            $table->decimal('monto', 10, 2)->nullable();   // monto reclamado (US$)
            $table->text('descripcion_bien');
            $table->text('detalle');
            $table->text('pedido');
            $table->string('estado', 15)->default('pendiente'); // pendiente | atendido
            $table->text('respuesta')->nullable();
            $table->timestamp('atendido_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamos');
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('destacado');
        });
    }
};
