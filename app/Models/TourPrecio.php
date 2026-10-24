<?php

namespace App\Models;

use App\Models\Concerns\Traducible;
use Illuminate\Database\Eloquent\Model;

class TourPrecio extends Model
{
    use Traducible;

    protected $table = 'tour_precios';

    protected $fillable = ['tour_id', 'orden', 'etiqueta', 'precio', 'descripcion', 'traducciones'];

    protected function casts(): array
    {
        return ['traducciones' => 'array', 'precio' => 'decimal:2'];
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
