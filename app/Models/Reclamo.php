<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reclamo extends Model
{
    protected $table = 'reclamos';

    protected $fillable = [
        'codigo', 'tipo', 'nombre', 'documento_tipo', 'documento_numero', 'domicilio', 'telefono', 'email',
        'menor_edad', 'apoderado', 'bien', 'monto', 'descripcion_bien', 'detalle', 'pedido',
        'estado', 'respuesta', 'atendido_at', 'ip',
    ];

    protected function casts(): array
    {
        return ['menor_edad' => 'boolean', 'monto' => 'decimal:2', 'atendido_at' => 'datetime'];
    }
}
