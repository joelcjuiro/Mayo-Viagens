
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tour_id')
                ->nullable()
                ->constrained('tours')
                ->nullOnDelete();

            $table->string('nombre');
            $table->string('email');
            $table->string('telefono', 30);
            $table->date('fecha');
            $table->unsignedInteger('personas')->default(1);
            $table->text('mensaje')->nullable();
            $table->string('estado')->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};