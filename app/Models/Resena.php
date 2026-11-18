<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resena extends Model
{
    protected $table = 'resenas';

    protected $fillable = ['tour_id', 'nombre', 'pais', 'email', 'calificacion', 'comentario', 'estado', 'respuesta', 'fotos', 'ip'];

    protected function casts(): array
    {
        return ['fotos' => 'array'];
    }

    /** URLs de las fotos subidas por el viajero. */
    public function fotosUrls(): array
    {
        return collect($this->fotos ?: [])->map(fn ($r) => Tour::urlDe($r))->filter()->values()->all();
    }

    /** Fotos con su miniatura: [['full' => url, 'thumb' => url], ...]. La lista usa la miniatura; la foto grande solo se pide al abrirla. */
    public function fotosGaleria(): array
    {
        return collect($this->fotos ?: [])->map(fn ($r) => [
            'full' => Tour::urlDe($r),
            'thumb' => Tour::urlDe(\App\Support\Imagenes::miniaturaDe($r)),
        ])->filter(fn ($f) => $f['full'])->values()->all();
    }

    public function scopeAprobadas($q)
    {
        return $q->where('estado', 'aprobada');
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }
}
