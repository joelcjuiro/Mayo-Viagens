<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resena extends Model
{
    protected $table = 'resenas';

    protected $fillable = ['tour_id', 'nombre', 'pais', 'email', 'calificacion', 'comentario', 'estado', 'respuesta', 'ip'];

    public function scopeAprobadas($q)
    {
        return $q->where('estado', 'aprobada');
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
