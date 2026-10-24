<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tour extends Model
{
    protected $fillable = [
        'nombre', 'slug', 'descripcion', 'itinerario', 'duracion', 'precio', 'imagen',
        'incluye', 'no_incluye', 'activo',
        'destino', 'categoria', 'nivel', 'tamano_grupo', 'badge', 'descuento', 'traducciones',
        'borrador', 'destacado', 'video_url', 'imagenes', 'adicionales', 'meta_descripcion', 'palabras_clave',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'activo' => 'boolean',
            'traducciones' => 'array',
            'borrador' => 'boolean',
            'destacado' => 'boolean',
            'imagenes' => 'array',
            'adicionales' => 'array',
        ];
    }

    /** Tours que se ven en el sitio público: publicados y visibles. */
    public function scopePublicos($query)
    {
        return $query->where('activo', true)->where('borrador', false);
    }

    public function secciones()
    {
        return $this->belongsToMany(Seccion::class, 'seccion_tour', 'tour_id', 'seccion_id');
    }

    public function resenas()
    {
        return $this->hasMany(Resena::class);
    }

    public function itinerarioItems()
    {
        return $this->hasMany(TourItinerario::class)->orderBy('orden');
    }

    public function precios()
    {
        return $this->hasMany(TourPrecio::class)->orderBy('orden');
    }

    /** Estado para el panel: borrador | publicado | oculto */
    protected function estado(): Attribute
    {
        return Attribute::get(fn () => $this->borrador ? 'borrador' : ($this->activo ? 'publicado' : 'oculto'));
    }

    /** URL pública propia del tour (por slug). */
    protected function url(): Attribute
    {
        return Attribute::get(fn () => route('tour.detalle', $this->slug));
    }

    /** Rutas de todas las imágenes de la galería convertidas en URL. */
    protected function galeriaUrls(): Attribute
    {
        return Attribute::get(function () {
            $rutas = $this->imagenes ?: array_filter([$this->imagen]);

            return collect($rutas)->map(fn ($r) => self::urlDe($r))->values()->all();
        });
    }

    /** ID de YouTube para incrustar el video (o null si el enlace no es válido). */
    protected function videoId(): Attribute
    {
        return Attribute::get(function () {
            $u = trim((string) $this->video_url);
            if ($u === '') {
                return null;
            }
            if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', $u, $m)) {
                return $m[1];
            }

            return null;
        });
    }

    public static function urlDe(?string $ruta): ?string
    {
        if (! $ruta) {
            return null;
        }

        return Str::startsWith($ruta, ['http://', 'https://', '//'])
            ? $ruta
            : asset(implode('/', array_map('rawurlencode', explode('/', ltrim($ruta, '/')))));
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class);
    }

    protected function imagenUrl(): Attribute
    {
        return Attribute::get(function () {
            return self::urlDe($this->imagen);
        });
    }

    /**
     * Texto del campo en el idioma activo. El portugués es el idioma base (columnas
     * originales); es/en salen de la columna `traducciones`, con respaldo al base.
     */
    public function t(string $campo): ?string
    {
        $locale = app()->getLocale();
        $valor = $locale === 'pt' ? null : ($this->traducciones[$locale][$campo] ?? null);

        return filled($valor) ? $valor : $this->getAttribute($campo);
    }

    protected function resumen(): Attribute
    {
        return Attribute::get(fn () => Str::limit(strip_tags((string) $this->t('descripcion')), 110));
    }

    protected function precioFinal(): Attribute
    {
        return Attribute::get(fn () => round($this->precio * (100 - (int) $this->descuento) / 100, 2));
    }

    protected function icono(): Attribute
    {
        return Attribute::get(function () {
            $n = Str::lower($this->nombre);

            return match (true) {
                Str::contains($n, ['city', 'cusco']) => 'landmark',
                Str::contains($n, ['laguna', 'lago', 'humantay']) => 'water',
                default => 'mountain',
            };
        });
    }
}