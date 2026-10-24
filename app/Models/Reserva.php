<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $fillable = [
        'tour_id',
        'nombre',
        'email',
        'telefono',
        'fecha',
        'personas',
        'mensaje',
        'estado',
        'terminos_aceptados_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}