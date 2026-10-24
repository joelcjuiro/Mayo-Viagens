<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'asunto',
        'mensaje',
        'leido',
        'terminos_aceptados_at',
    ];

    protected function casts(): array
    {
        return [
            'leido' => 'boolean',
        ];
    }
}
