<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->string('destino')->nullable();
            $table->string('categoria')->nullable();
            $table->string('nivel')->nullable();
            $table->unsignedInteger('tamano_grupo')->nullable();
            $table->string('badge')->nullable();
            $table->unsignedTinyInteger('descuento')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['destino', 'categoria', 'nivel', 'tamano_grupo', 'badge', 'descuento']);
        });
    }
};