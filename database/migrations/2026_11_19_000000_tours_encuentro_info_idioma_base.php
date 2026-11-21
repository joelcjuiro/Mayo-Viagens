<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            if (! Schema::hasColumn('tours', 'encuentro')) {
                $table->text('encuentro')->nullable();          // Encuentro y recogida
            }
            if (! Schema::hasColumn('tours', 'info_importante')) {
                $table->text('info_importante')->nullable();    // Información importante
            }
            if (! Schema::hasColumn('tours', 'idioma_base')) {
                $table->string('idioma_base', 2)->default('es'); // idioma en el que se escribió el tour
            }
        });

        if (Schema::hasTable('articulos') && ! Schema::hasColumn('articulos', 'idioma_base')) {
            Schema::table('articulos', function (Blueprint $table) {
                $table->string('idioma_base', 2)->default('es');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['encuentro', 'info_importante', 'idioma_base']);
        });
        if (Schema::hasTable('articulos') && Schema::hasColumn('articulos', 'idioma_base')) {
            Schema::table('articulos', fn (Blueprint $t) => $t->dropColumn('idioma_base'));
        }
    }
};
