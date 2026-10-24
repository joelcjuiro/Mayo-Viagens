<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fotos que sube el viajero junto a su opinión (hasta 3)
        if (Schema::hasTable('resenas') && ! Schema::hasColumn('resenas', 'fotos')) {
            Schema::table('resenas', function (Blueprint $table) {
                $table->json('fotos')->nullable();
            });
        }

        // Roles del panel: admin (todo) y editor (contenido, sin borrar ni administrar usuarios)
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 12)->default('admin'); // los usuarios que ya existen quedan como admin
            }
            if (! Schema::hasColumn('users', 'activo')) {
                $table->boolean('activo')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'activo']);
        });
        if (Schema::hasTable('resenas')) {
            Schema::table('resenas', function (Blueprint $table) {
                $table->dropColumn('fotos');
            });
        }
    }
};
