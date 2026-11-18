<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Resena;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('articulos'), 503, 'Falta actualizar la base de datos. En la terminal del proyecto ejecuta: php artisan migrate');

        $categoria = in_array($request->query('categoria'), Articulo::CATEGORIAS, true) ? $request->query('categoria') : null;
        $q = trim((string) $request->query('q', ''));
        $filtrando = $categoria !== null || $q !== '';

        $consulta = Articulo::publicados()
            ->when($categoria, fn ($x) => $x->where('categoria', $categoria))
            ->when($q !== '', function ($x) use ($q) {
                $like = '%'.str_replace(['\\', '%', '_'], ' ', mb_substr($q, 0, 60)).'%';
                $x->where(fn ($w) => $w->where('titulo', 'like', $like)->orWhere('resumen', 'like', $like)
                    ->orWhere('contenido', 'like', $like)->orWhere('traducciones', 'like', $like));
            });

        // El destacado abre la página (solo en la primera página y sin filtros)
        $destacado = null;
        if (! $filtrando && (int) $request->query('page', 1) === 1) {
            $destacado = Articulo::publicados()->where('destacado', true)->latest('publicado_at')->first()
                ?? Articulo::publicados()->latest('publicado_at')->first();
        }

        $articulos = $consulta
            ->when($destacado, fn ($x) => $x->where('id', '!=', $destacado->id))
            ->orderByDesc('publicado_at')->orderByDesc('id')
            ->paginate(9)->withQueryString();

        // Pasajeros: últimas opiniones aprobadas que traen foto
        $viajeros = rescue(fn () => Resena::aprobadas()->whereNotNull('fotos')->with('tour:id,nombre,slug')->latest()->take(6)->get(), collect(), false);

        return view('blog.index', [
            'articulos' => $articulos,
            'destacado' => $destacado,
            'categoria' => $categoria,
            'q' => $q,
            'filtrando' => $filtrando,
            'viajeros' => $viajeros,
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $articulo = Articulo::publicados()->where('slug', $slug)->firstOrFail();

        // Cuenta una vista por sesión; no cuenta a robots ni al equipo logueado
        $clave = 'blog.visto.'.$articulo->id;
        if (! $request->session()->has($clave) && ! auth()->check() && ! preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview/i', (string) $request->userAgent())) {
            DB::table('articulos')->where('id', $articulo->id)->increment('vistas');
            $request->session()->put($clave, true);
        }

        return view('blog.show', $this->datosDetalle($articulo) + ['vistaPrevia' => false]);
    }

    /** Datos de la página de un artículo (también los usa la vista previa del panel). */
    public static function datosDetalle(Articulo $articulo): array
    {
        $relacionados = Articulo::publicados()->where('id', '!=', $articulo->id)
            ->orderByRaw('categoria = ? desc', [$articulo->categoria])
            ->latest('publicado_at')->take(3)->get();

        return ['articulo' => $articulo, 'relacionados' => $relacionados];
    }
}
