<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->boolean('borrador')->default(false);          // guardado como borrador (no se ve en el sitio)
            $table->string('video_url')->nullable();              // enlace de YouTube
            $table->json('imagenes')->nullable();                 // hasta 7 rutas (la primera es la portada)
            $table->json('adicionales')->nullable();              // [{nombre, precio}]
            $table->string('meta_descripcion', 300)->nullable();  // SEO
            $table->string('palabras_clave')->nullable();         // SEO + buscador del sitio
        });

        // Secciones que se muestran en el inicio (Tour místico, Tour clásico, Machu Picchu...)
        Schema::create('secciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Secciones iniciales (se pueden renombrar, reordenar o borrar desde el panel)
        $ahora = now();
        foreach ([
            ['Tour místico', 'tour-mistico'],
            ['Tour clásico', 'tour-clasico'],
            ['Machu Picchu', 'machu-picchu'],
            ['Nuevo tour', 'nuevo-tour'],
            ['Experiencias diferentes', 'experiencias-diferentes'],
            ['Tour en Lima', 'tour-en-lima'],
            ['Tour en Puno', 'tour-en-puno'],
            ['Tour en Arequipa', 'tour-en-arequipa'],
        ] as $i => [$nombre, $slug]) {
            DB::table('secciones')->insert([
                'nombre' => $nombre, 'slug' => $slug, 'orden' => $i + 1,
                'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        Schema::create('seccion_tour', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('secciones')->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->unique(['seccion_id', 'tour_id']);
        });

        Schema::create('tour_itinerarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->string('titulo');
            $table->string('horario')->nullable();     // "06:00", "Día 2", "Mañana"...
            $table->text('descripcion')->nullable();
            $table->json('imagenes')->nullable();      // hasta 3 rutas
            $table->timestamps();
        });

        Schema::create('tour_precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->string('etiqueta');                 // "Adulto", "Niño", "Grupo privado 2-4"...
            $table->decimal('precio', 10, 2)->default(0); // siempre en dólares (US$)
            $table->text('descripcion')->nullable();    // qué más incluye este precio
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_precios');
        Schema::dropIfExists('tour_itinerarios');
        Schema::dropIfExists('seccion_tour');
        Schema::dropIfExists('secciones');

        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['borrador', 'video_url', 'imagenes', 'adicionales', 'meta_descripcion', 'palabras_clave']);
        });
    }
};
