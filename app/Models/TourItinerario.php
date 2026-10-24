<?php

namespace App\Models;

use App\Models\Concerns\Traducible;
use Illuminate\Database\Eloquent\Model;

class TourItinerario extends Model
{
    use Traducible;

    protected $table = 'tour_itinerarios';

    protected $fillable = ['tour_id', 'orden', 'titulo', 'horario', 'descripcion', 'imagenes', 'traducciones'];

    protected function casts(): array
    {
        return ['traducciones' => 'array', 'imagenes' => 'array'];
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
