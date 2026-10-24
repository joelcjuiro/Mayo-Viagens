<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->timestamp('terminos_aceptados_at')->nullable();
        });

        Schema::table('contactos', function (Blueprint $table) {
            $table->timestamp('terminos_aceptados_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservas', fn (Blueprint $t) => $t->dropColumn('terminos_aceptados_at'));
        Schema::table('contactos', fn (Blueprint $t) => $t->dropColumn('terminos_aceptados_at'));
    }
};