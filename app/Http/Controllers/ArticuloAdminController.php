<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Support\Imagenes;
use App\Support\Traductor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Panel del blog: crear, editar, programar y publicar novedades. */
class ArticuloAdminController extends Controller
{
    public const CARPETA = 'uploads/blog';

    public function index(Request $request)
    {
        $estado = in_array($request->query('estado'), ['publicado', 'programado', 'borrador'], true) ? $request->query('estado') : null;
        $q = trim((string) $request->query('q', ''));

        $lista = Articulo::query()
            ->when($q !== '', fn ($x) => $x->where('titulo', 'like', '%'.str_replace(['%', '_', '\\'], ' ', $q).'%'))
            ->when($estado === 'borrador', fn ($x) => $x->where('estado', '!=', 'publicado'))
            ->when($estado === 'publicado', fn ($x) => $x->publicados())
            ->when($estado === 'programado', fn ($x) => $x->where('estado', 'publicado')->where('publicado_at', '>', now()))
            ->latest('updated_at')->paginate(12)->withQueryString();

        return view('admin.articulos', [
            'articulos' => $lista,
            'estado' => $estado,
            'q' => $q,
            'conteo' => [
                'todos' => Articulo::count(),
                'publicado' => Articulo::publicados()->count(),
                'programado' => Articulo::where('estado', 'publicado')->where('publicado_at', '>', now())->count(),
                'borrador' => Articulo::where('estado', '!=', 'publicado')->count(),
            ],
            'vistasTotales' => (int) Articulo::sum('vistas'),
        ]);
    }

    public function create()
    {
        return view('admin.articulo-form', ['articulo' => new Articulo(['categoria' => 'novedad', 'idioma_base' => 'es']), 'traductor' => Traductor::disponible()]);
    }

    public function edit(string $id)
    {
        return view('admin.articulo-form', ['articulo' => Articulo::findOrFail($id), 'traductor' => Traductor::disponible()]);
    }

    public function store(Request $request)
    {
        return $this->guardar($request, new Articulo());
    }

    public function update(Request $request, string $id)
    {
        return $this->guardar($request, Articulo::findOrFail($id));
    }

    private function guardar(Request $request, Articulo $articulo)
    {
        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180'],
            'categoria' => ['required', 'in:'.implode(',', Articulo::CATEGORIAS)],
            'resumen' => ['nullable', 'string', 'max:320'],
            'contenido' => ['required', 'string', 'max:60000'],
            'imagen_alt' => ['nullable', 'string', 'max:160'],
            'meta_descripcion' => ['nullable', 'string', 'max:200'],
            'publicado_at' => ['nullable', 'date'],
            'portada' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'idioma_base' => ['required', 'in:pt,es,en'],
            'tr' => ['nullable', 'array'],
            'tr.*.*' => ['nullable', 'string', 'max:60000'],
            'accion' => ['required', 'in:borrador,publicar'],
        ], [
            'portada.max' => 'La portada pesa demasiado (máximo 15 MB). Se reduce sola al subirla, pero ese archivo es excesivo.',
            'portada.image' => 'La portada debe ser una imagen JPG, PNG o WebP.',
        ]);

        $base = $datos['idioma_base'];
        $slugPedido = Str::slug((string) ($datos['slug'] ?? ''));
        if ($slugPedido !== '') {
            $slug = Articulo::slugUnico($slugPedido, $articulo->id);
        } elseif ($articulo->exists && filled($articulo->slug)) {
            $slug = $articulo->slug;
        } else {
            $slug = Articulo::slugUnico($datos['titulo']);
        }

        $publicar = $datos['accion'] === 'publicar';
        $fecha = filled($datos['publicado_at'] ?? null) ? Carbon::parse($datos['publicado_at']) : null;
        if ($publicar && ! $fecha) {
            $fecha = $articulo->publicado_at && $articulo->estado === 'publicado' ? $articulo->publicado_at : now();
        }

        $campos = [
            'titulo' => $datos['titulo'],
            'slug' => $slug,
            'categoria' => $datos['categoria'],
            'resumen' => $datos['resumen'] ?? null,
            'contenido' => $datos['contenido'],
            'imagen_alt' => $datos['imagen_alt'] ?? null,
            'meta_descripcion' => $datos['meta_descripcion'] ?? null,
            'idioma_base' => $base,
            'estado' => $publicar ? 'publicado' : 'borrador',
            'publicado_at' => $fecha,
            'destacado' => $request->boolean('destacado'),
            'traducciones' => Articulo::limpiarTraducciones(
                collect($datos['tr'] ?? [])->only(['pt', 'es', 'en'])->all(),
                ['titulo', 'resumen', 'contenido', 'meta_descripcion', 'imagen_alt']
            ),
        ];

        // Portada: se reduce y se guarda en WebP con miniatura (nunca llega pesada a la página)
        if ($request->hasFile('portada')) {
            $g = Imagenes::guardarSubida($request->file('portada'), self::CARPETA, ['lado' => 1600, 'calidad' => 80, 'max_bytes' => 320 * 1024, 'miniatura' => 640]);
            if (! $g) {
                return back()->withInput()->withErrors(['portada' => 'No se pudo leer la imagen. Prueba con otra foto JPG, PNG o WebP.']);
            }
            Imagenes::borrarSubida($articulo->imagen);
            $campos['imagen'] = $g['ruta'];
            $campos['imagen_miniatura'] = $g['miniatura'];
        } elseif ($request->boolean('quitar_portada') && $articulo->imagen) {
            Imagenes::borrarSubida($articulo->imagen);
            $campos['imagen'] = null;
            $campos['imagen_miniatura'] = null;
        }

        if (! $articulo->exists) {
            $campos['autor_id'] = $request->user()?->id;
        }

        $articulo->fill($campos)->save();
        Cache::forget('mayo.sitemap');

        $msg = $publicar
            ? ($fecha && $fecha->isFuture() ? 'Artículo programado para el '.$fecha->format('d/m/Y H:i').'.' : 'Artículo publicado. Ya se ve en el blog.')
            : 'Borrador guardado.';

        return redirect()->route('admin.blog.edit', $articulo->id)->with('success', $msg);
    }

    public function estado(Request $request, string $id)
    {
        $articulo = Articulo::findOrFail($id);
        $publicar = $request->input('accion') === 'publicar';
        $articulo->update([
            'estado' => $publicar ? 'publicado' : 'borrador',
            'publicado_at' => $publicar ? ($articulo->publicado_at ?: now()) : $articulo->publicado_at,
        ]);
        Cache::forget('mayo.sitemap');

        return back()->with('success', $publicar ? 'Artículo publicado.' : 'Artículo pasado a borrador (ya no se ve en el sitio).');
    }

    public function preview(string $id)
    {
        $articulo = Articulo::findOrFail($id);

        return view('blog.show', BlogController::datosDetalle($articulo) + ['vistaPrevia' => true]);
    }

    public function destroy(string $id)
    {
        $articulo = Articulo::findOrFail($id);
        Imagenes::borrarSubida($articulo->imagen);
        $articulo->delete();
        Cache::forget('mayo.sitemap');

        return redirect()->route('admin.blog')->with('success', 'Artículo eliminado.');
    }
}
