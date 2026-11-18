<?php

namespace App\Models;

use App\Models\Concerns\Traducible;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Entrada del blog: novedades, avisos, guías y consejos. Idioma base: portugués. */
class Articulo extends Model
{
    use Traducible;

    protected $table = 'articulos';

    public const CATEGORIAS = ['novedad', 'aviso', 'guia', 'consejo'];

    protected $fillable = [
        'titulo', 'slug', 'categoria', 'resumen', 'contenido', 'imagen', 'imagen_miniatura', 'imagen_alt',
        'estado', 'publicado_at', 'destacado', 'meta_descripcion', 'traducciones', 'autor_id',
    ];

    protected function casts(): array
    {
        return [
            'traducciones' => 'array',
            'destacado' => 'boolean',
            'publicado_at' => 'datetime',
        ];
    }

    /** Visibles en el sitio: publicados y con fecha de publicación ya cumplida. */
    public function scopePublicados($q)
    {
        return $q->where('estado', 'publicado')
            ->where(fn ($w) => $w->whereNull('publicado_at')->orWhere('publicado_at', '<=', now()));
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }

    /** borrador | programado | publicado (para el panel) */
    protected function situacion(): Attribute
    {
        return Attribute::get(function () {
            if ($this->estado !== 'publicado') {
                return 'borrador';
            }

            return $this->publicado_at && $this->publicado_at->isFuture() ? 'programado' : 'publicado';
        });
    }

    protected function url(): Attribute
    {
        return Attribute::get(fn () => route('blog.show', $this->slug));
    }

    protected function imagenUrl(): Attribute
    {
        return Attribute::get(fn () => Tour::urlDe($this->imagen));
    }

    protected function miniaturaUrl(): Attribute
    {
        return Attribute::get(fn () => Tour::urlDe($this->imagen_miniatura ?: $this->imagen));
    }

    /** Fecha mostrada: la de publicación, o la de creación si todavía no tiene. */
    public function fecha()
    {
        return $this->publicado_at ?: $this->created_at;
    }

    /** Markdown seguro -> HTML. El HTML pegado por el autor se elimina; las imágenes cargan en diferido. */
    public function html(): string
    {
        $html = Str::markdown((string) $this->t('contenido'), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = preg_replace('/<img\s/i', '<img loading="lazy" decoding="async" ', $html);

        // Los enlaces externos se abren en pestaña nueva
        return preg_replace_callback('/<a href="(https?:\/\/[^"]+)"/i', function ($m) {
            $host = parse_url($m[1], PHP_URL_HOST);

            return $host && $host !== request()->getHost()
                ? '<a href="'.$m[1].'" target="_blank" rel="noopener noreferrer"'
                : $m[0];
        }, $html);
    }

    /** Minutos de lectura (≈200 palabras por minuto). */
    public function minutos(): int
    {
        $palabras = preg_split('/\s+/u', trim(strip_tags((string) $this->t('contenido'))), -1, PREG_SPLIT_NO_EMPTY);

        return max(1, (int) ceil(count($palabras ?: []) / 200));
    }

    public function extracto(int $limite = 150): string
    {
        $r = trim((string) $this->t('resumen'));

        return $r !== '' ? $r : Str::limit(trim(strip_tags(Str::markdown((string) $this->t('contenido'), ['html_input' => 'strip']))), $limite);
    }

    /** Slug único a partir del título. */
    public static function slugUnico(string $titulo, ?int $ignorarId = null): string
    {
        $base = Str::slug($titulo) ?: 'novedad';
        $slug = $base;
        $n = 2;
        while (static::where('slug', $slug)->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
