<?php

namespace App\Models;

use App\Models\Concerns\Traducible;
use Illuminate\Database\Eloquent\Model;

class Seccion extends Model
{
    use Traducible;

    protected $table = 'secciones';

    protected $fillable = ['nombre', 'slug', 'descripcion', 'orden', 'activo', 'traducciones'];

    protected function casts(): array
    {
        return ['traducciones' => 'array', 'activo' => 'boolean'];
    }

    public function tours()
    {
        return $this->belongsToMany(Tour::class, 'seccion_tour', 'seccion_id', 'tour_id');
    }

    /** Tours que realmente se ven en el sitio (publicados y visibles). */
    public function toursPublicos()
    {
        return $this->tours()->where('tours.activo', true)->where('tours.borrador', false);
    }
}
