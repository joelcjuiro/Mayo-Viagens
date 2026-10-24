<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tour_itinerarios', 'tour_precios', 'secciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->json('traducciones')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['tour_itinerarios', 'tour_precios', 'secciones'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('traducciones');
            });
        }
    }
};
