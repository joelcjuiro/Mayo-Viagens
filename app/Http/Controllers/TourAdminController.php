<?php

namespace App\Http\Controllers;

use App\Models\Seccion;
use App\Models\Tour;
use App\Models\TourItinerario;
use App\Models\TourPrecio;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TourAdminController extends Controller
{
    public const MAX_IMAGENES = 7;        // por tour
    public const MAX_IMAGENES_PASO = 3;   // por paso del itinerario
    public const MAX_KB = 300;            // peso máximo por imagen
    public const CARPETA = 'uploads/tours';

    /* ------------------------------------------------------------------ */
    /*  Listado                                                            */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $estado = in_array($request->query('estado'), ['publicado', 'borrador', 'oculto'], true) ? $request->query('estado') : null;
        $q = trim((string) $request->query('q', ''));
        $seccionId = $request->query('seccion');

        $tours = Tour::with(['secciones', 'precios'])
            ->when($estado === 'borrador', fn ($w) => $w->where('borrador', true))
            ->when($estado === 'publicado', fn ($w) => $w->publicos())
            ->when($estado === 'oculto', fn ($w) => $w->where('borrador', false)->where('activo', false))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('nombre', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%")))
            ->when($seccionId, fn ($w) => $w->whereHas('secciones', fn ($x) => $x->where('secciones.id', $seccionId)))
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $conteo = [
            'todos' => Tour::count(),
            'publicado' => Tour::publicos()->count(),
            'borrador' => Tour::where('borrador', true)->count(),
            'oculto' => Tour::where('borrador', false)->where('activo', false)->count(),
        ];

        return view('admin.tours', [
            'tours' => $tours,
            'estado' => $estado,
            'q' => $q,
            'seccionId' => $seccionId,
            'secciones' => Seccion::orderBy('orden')->get(),
            'conteo' => $conteo,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  Formulario                                                         */
    /* ------------------------------------------------------------------ */

    public function create()
    {
        return view('admin.tour-form', $this->datosFormulario(new Tour(['activo' => true])));
    }

    public function edit(string $id)
    {
        $tour = Tour::with(['itinerarioItems', 'precios', 'secciones'])->findOrFail($id);

        return view('admin.tour-form', $this->datosFormulario($tour));
    }

    public function preview(string $id)
    {
        $tour = Tour::with(['itinerarioItems', 'precios'])->findOrFail($id);

        return view('tour-detalle', ['tour' => $tour, 'vistaPrevia' => true]);
    }

    /** Datos que alimentan el formulario: primero lo guardado, o lo que se escribió si hubo errores. */
    private function datosFormulario(Tour $tour): array
    {
        $lineas = fn ($t) => array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $t))));

        // Precios: los guardados, o el precio antiguo de un solo valor.
        $precios = $tour->exists ? $tour->precios->map(fn ($p) => [
            'etiqueta' => $p->etiqueta, 'precio' => (float) $p->precio, 'descripcion' => $p->descripcion, 'tr' => $p->traducciones ?: [],
        ])->all() : [];
        if (! $precios && $tour->exists && (float) $tour->precio > 0) {
            $precios = [['etiqueta' => 'Por persona', 'precio' => (float) $tour->precio, 'descripcion' => '']];
        }

        // Itinerario: los pasos guardados, o las líneas del itinerario antiguo (texto).
        $pasos = $tour->exists ? $tour->itinerarioItems->map(fn ($i) => [
            'titulo' => $i->titulo, 'horario' => $i->horario, 'descripcion' => $i->descripcion, 'imagenes' => $i->imagenes ?: [], 'tr' => $i->traducciones ?: [],
        ])->all() : [];
        if (! $pasos && $tour->exists) {
            $pasos = array_map(fn ($l) => ['titulo' => $l, 'horario' => '', 'descripcion' => '', 'imagenes' => []], $lineas($tour->itinerario));
        }

        $imagenes = $tour->imagenes ?: array_values(array_filter([$tour->imagen]));

        return [
            'tour' => $tour,
            'secciones' => Seccion::orderBy('orden')->get(),
            'idsSecciones' => $tour->exists ? $tour->secciones->pluck('id')->all() : [],
            'precios' => $precios,
            'pasos' => $pasos,
            'imagenes' => array_map(fn ($r) => ['ruta' => $r, 'url' => Tour::urlDe($r)], $imagenes),
            'incluye' => $lineas($tour->incluye),
            'noIncluye' => $lineas($tour->no_incluye),
            'adicionales' => $tour->adicionales ?: [],
            'maxImagenes' => self::MAX_IMAGENES,
            'maxImagenesPaso' => self::MAX_IMAGENES_PASO,
            'maxKb' => self::MAX_KB,
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Guardar                                                            */
    /* ------------------------------------------------------------------ */

    public function store(Request $request)
    {
        return $this->guardar($request, new Tour());
    }

    public function update(Request $request, string $id)
    {
        return $this->guardar($request, Tour::findOrFail($id));
    }

    private function guardar(Request $request, Tour $tour)
    {
        $esBorrador = $request->input('accion') === 'borrador';
        $nuevo = ! $tour->exists;

        $datos = $request->validate($this->reglas($tour, $request), $this->mensajes());

        // --- Imágenes del tour (portada = la primera) -------------------
        $permitidasTour = $tour->exists ? ($tour->imagenes ?: array_values(array_filter([$tour->imagen]))) : [];
        $permitidasPasos = $tour->exists ? $tour->itinerarioItems->flatMap(fn ($i) => $i->imagenes ?: [])->all() : [];
        $conocidas = array_merge($permitidasTour, $permitidasPasos);

        $nuevosArchivos = $this->archivos($request->file('imagenes_nuevas'));
        $galeria = $this->resolverOrden((array) $request->input('imagenes_orden', []), $conocidas, $nuevosArchivos);

        // --- Pasos del itinerario ---------------------------------------
        $pasosEntrada = [];
        foreach ((array) $request->input('itinerario', []) as $k => $paso) {
            $titulo = trim((string) ($paso['titulo'] ?? ''));
            $descripcion = trim((string) ($paso['descripcion'] ?? ''));
            $horario = trim((string) ($paso['horario'] ?? ''));
            $imgs = $this->resolverOrden(
                (array) ($paso['imagenes_orden'] ?? []),
                $conocidas,
                $this->archivos($request->file("itinerario.$k.imagenes_nuevas"))
            );
            if ($titulo === '' && $descripcion === '' && ! $imgs) {
                continue;
            }
            $pasosEntrada[] = [
                'titulo' => $titulo !== '' ? $titulo : 'Paso '.(count($pasosEntrada) + 1),
                'horario' => $horario !== '' ? $horario : null,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
                'imagenes' => array_slice($imgs, 0, self::MAX_IMAGENES_PASO),
                'traducciones' => TourItinerario::limpiarTraducciones($paso['tr'] ?? [], ['titulo', 'descripcion']),
            ];
        }

        // --- Precios (siempre en US$) -----------------------------------
        $preciosEntrada = [];
        foreach ((array) $request->input('precios', []) as $p) {
            $etiqueta = trim((string) ($p['etiqueta'] ?? ''));
            $valor = ($p['precio'] ?? '') === '' ? null : (float) $p['precio'];
            $desc = trim((string) ($p['descripcion'] ?? ''));
            if ($etiqueta === '' && $valor === null && $desc === '') {
                continue;
            }
            $preciosEntrada[] = [
                'etiqueta' => $etiqueta !== '' ? $etiqueta : 'Por persona',
                'precio' => $valor ?? 0,
                'descripcion' => $desc !== '' ? $desc : null,
                'traducciones' => TourPrecio::limpiarTraducciones($p['tr'] ?? [], ['etiqueta', 'descripcion']),
            ];
        }

        // --- Para PUBLICAR el tour tiene que estar completo ---------------
        if (! $esBorrador) {
            $faltan = [];
            if (blank($request->input('descripcion'))) {
                $faltan['descripcion'] = 'Tour: escribe la descripción para poder publicar.';
            }
            if (! $galeria) {
                $faltan['imagenes_nuevas'] = 'Tour: sube al menos una imagen para poder publicar.';
            }
            if (! collect($preciosEntrada)->contains(fn ($p) => $p['precio'] > 0)) {
                $faltan['precios'] = 'Precio: agrega al menos un precio mayor a 0 (US$) para poder publicar.';
            }
            if ($faltan) {
                throw ValidationException::withMessages($faltan + ['_hint' => 'Si aún no está listo, usa «Guardar borrador».']);
            }
        }

        if (count($galeria) > self::MAX_IMAGENES) {
            throw ValidationException::withMessages(['imagenes_nuevas' => 'Tour: máximo '.self::MAX_IMAGENES.' imágenes.']);
        }

        // --- Guardar todo en una transacción ------------------------------
        $archivosNuevos = [];   // para borrarlos si algo falla
        try {
            DB::transaction(function () use (&$tour, $request, $datos, $esBorrador, $galeria, $pasosEntrada, $preciosEntrada, &$archivosNuevos, $nuevo) {
                // Subir los archivos nuevos y reemplazar los marcadores por su ruta final
                $galeria = $this->subirMarcadores($galeria, $archivosNuevos);
                foreach ($pasosEntrada as &$paso) {
                    $paso['imagenes'] = $this->subirMarcadores($paso['imagenes'], $archivosNuevos);
                }
                unset($paso);

                $incluye = $this->listaATexto($request->input('incluye_items'));
                $noIncluye = $this->listaATexto($request->input('no_incluye_items'));

                $adicionales = collect((array) $request->input('adicionales', []))
                    ->map(fn ($a) => [
                        'nombre' => trim((string) ($a['nombre'] ?? '')),
                        'precio' => ($a['precio'] ?? '') === '' ? null : (float) $a['precio'],
                    ])
                    ->filter(fn ($a) => $a['nombre'] !== '')->values()->all();

                $precioDesde = collect($preciosEntrada)->pluck('precio')->filter(fn ($v) => $v > 0)->min() ?? 0;

                $slug = $this->slugUnico($datos['slug'] ?? null, $datos['nombre'], $tour);

                $campos = [
                    'nombre' => $datos['nombre'],
                    'slug' => $slug,
                    'descripcion' => (string) ($datos['descripcion'] ?? ''),
                    'duracion' => $datos['duracion'] ?? null,
                    'destino' => $datos['destino'] ?? null,
                    'categoria' => $datos['categoria'] ?? null,
                    'nivel' => $datos['nivel'] ?? null,
                    'tamano_grupo' => $datos['tamano_grupo'] ?? null,
                    'badge' => $datos['badge'] ?? null,
                    'descuento' => (int) ($datos['descuento'] ?? 0),
                    'video_url' => $datos['video_url'] ?? null,
                    'destacado' => $request->boolean('destacado'),
                    'meta_descripcion' => $datos['meta_descripcion'] ?? null,
                    'palabras_clave' => $datos['palabras_clave'] ?? null,
                    'incluye' => $incluye,
                    'no_incluye' => $noIncluye,
                    'adicionales' => $adicionales ?: null,
                    'imagenes' => $galeria ?: null,
                    'imagen' => $galeria[0] ?? null,
                    'precio' => $precioDesde,
                    'traducciones' => $this->traducciones($request, $tour),
                    // Borrador = no se ve en el sitio. Publicar = visible.
                    'borrador' => $esBorrador,
                    'activo' => ! $esBorrador,
                ];

                // El texto "itinerario" se mantiene sincronizado (lo usan el panel y la página pública antigua)
                if ($pasosEntrada) {
                    $campos['itinerario'] = collect($pasosEntrada)->pluck('titulo')->implode("\n");
                } elseif ($tour->exists && $tour->itinerarioItems()->exists()) {
                    $campos['itinerario'] = null;
                }

                $tour->fill($campos)->save();

                // Itinerario y precios: se reemplazan por lo que llegó del formulario
                $tour->itinerarioItems()->delete();
                foreach ($pasosEntrada as $i => $paso) {
                    $tour->itinerarioItems()->create($paso + ['orden' => $i]);
                }
                $tour->precios()->delete();
                foreach ($preciosEntrada as $i => $p) {
                    $tour->precios()->create($p + ['orden' => $i]);
                }

                // Secciones del inicio
                $tour->secciones()->sync(array_map('intval', (array) $request->input('secciones', [])));
            });
        } catch (\Throwable $e) {
            foreach ($archivosNuevos as $ruta) {
                $this->borrarArchivo($ruta);
            }
            throw $e;
        }

        // Borrar del disco las imágenes que ya no se usan
        $vigentes = array_merge(
            $tour->imagenes ?: [],
            $tour->itinerarioItems()->get()->flatMap(fn ($i) => $i->imagenes ?: [])->all()
        );
        foreach (array_diff($conocidas, $vigentes) as $ruta) {
            $this->borrarArchivo($ruta);
        }

        \Illuminate\Support\Facades\Cache::forget('mayo.site-stats');

        if ($esBorrador) {
            return redirect()
                ->route('admin.tours.edit', ['id' => $tour->id, 'panel' => $request->input('panel_actual')])
                ->with('success', 'Borrador guardado. No se ve en el sitio hasta que lo publiques.');
        }

        return redirect()
            ->route('admin.tours')
            ->with('success', $nuevo ? 'El tour se publicó correctamente.' : 'Los cambios del tour se guardaron y está publicado.');
    }

    /* ------------------------------------------------------------------ */
    /*  Acciones rápidas del listado                                      */
    /* ------------------------------------------------------------------ */

    /** Publicar un borrador / ocultar / volver a mostrar. */
    public function estado(Request $request, string $id)
    {
        $tour = Tour::findOrFail($id);
        $accion = $request->input('accion');

        if ($accion === 'ocultar') {
            $tour->update(['activo' => false]);
            $msg = 'El tour quedó oculto del sitio.';
        } else { // mostrar / publicar
            $faltan = $this->faltantes($tour);
            if ($faltan) {
                return redirect()->route('admin.tours.edit', $tour->id)
                    ->with('error', 'Antes de publicar completa: '.implode(', ', $faltan).'.');
            }
            $tour->update(['activo' => true, 'borrador' => false]);
            $msg = 'El tour ya es visible en el sitio.';
        }

        \Illuminate\Support\Facades\Cache::forget('mayo.site-stats');

        return back()->with('success', $msg);
    }

    public function duplicar(string $id)
    {
        $origen = Tour::with(['itinerarioItems', 'precios', 'secciones'])->findOrFail($id);

        $copia = DB::transaction(function () use ($origen) {
            $copia = $origen->replicate(['slug', 'imagen', 'imagenes']);
            $copia->nombre = $origen->nombre.' (copia)';
            $copia->slug = $this->slugUnico(null, $copia->nombre, new Tour());
            $copia->borrador = true;
            $copia->activo = false;
            $galeria = array_map(fn ($r) => $this->copiarArchivo($r), $origen->imagenes ?: array_filter([$origen->imagen]));
            $copia->imagenes = $galeria ?: null;
            $copia->imagen = $galeria[0] ?? null;
            $copia->save();

            foreach ($origen->itinerarioItems as $i) {
                $copia->itinerarioItems()->create([
                    'orden' => $i->orden, 'titulo' => $i->titulo, 'horario' => $i->horario, 'descripcion' => $i->descripcion,
                    'imagenes' => array_map(fn ($r) => $this->copiarArchivo($r), $i->imagenes ?: []) ?: null,
                    'traducciones' => $i->traducciones,
                ]);
            }
            foreach ($origen->precios as $p) {
                $copia->precios()->create(['orden' => $p->orden, 'etiqueta' => $p->etiqueta, 'precio' => $p->precio, 'descripcion' => $p->descripcion, 'traducciones' => $p->traducciones]);
            }
            $copia->secciones()->sync($origen->secciones->pluck('id')->all());

            return $copia;
        });

        return redirect()->route('admin.tours.edit', $copia->id)->with('success', 'Se creó una copia como borrador. Ajusta lo que necesites.');
    }

    public function destroy(string $id)
    {
        $tour = Tour::with('itinerarioItems')->findOrFail($id);
        $rutas = array_merge($tour->imagenes ?: [], $tour->itinerarioItems->flatMap(fn ($i) => $i->imagenes ?: [])->all());

        $tour->delete(); // itinerario, precios y secciones se borran en cascada

        foreach ($rutas as $ruta) {
            $this->borrarArchivo($ruta);
        }
        \Illuminate\Support\Facades\Cache::forget('mayo.site-stats');

        return redirect()->route('admin.tours')->with('success', 'El tour fue eliminado.');
    }

    /** Qué le falta a un tour para poder publicarse. */
    public static function faltantes(Tour $tour): array
    {
        $faltan = [];
        if (blank($tour->descripcion)) {
            $faltan[] = 'descripción';
        }
        if (blank($tour->imagen)) {
            $faltan[] = 'una imagen';
        }
        if ((float) $tour->precio <= 0) {
            $faltan[] = 'un precio';
        }

        return $faltan;
    }

    /* ------------------------------------------------------------------ */
    /*  Validación                                                         */
    /* ------------------------------------------------------------------ */

    private function reglas(Tour $tour, Request $request): array
    {
        $imgs = ['image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_KB];

        return [
            'accion' => ['required', Rule::in(['borrador', 'publicar'])],
            'nombre' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('tours', 'slug')->ignore($tour->id)],
            'duracion' => ['nullable', 'string', 'max:100'],
            'destino' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'nivel' => ['nullable', Rule::in(['Baixo', 'Médio', 'Alto'])],
            'tamano_grupo' => ['nullable', 'integer', 'min:1', 'max:500'],
            'badge' => ['nullable', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:255', function ($attr, $value, $fail) {
                if ($value && ! preg_match('~(youtu\.be/|youtube(-nocookie)?\.com/)~i', $value)) {
                    $fail('El enlace del video debe ser de YouTube.');
                }
            }],
            'adicionales' => ['nullable', 'array', 'max:20'],
            'adicionales.*.nombre' => ['nullable', 'string', 'max:120'],
            'adicionales.*.precio' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'incluye_items' => ['nullable', 'array'],
            'incluye_items.*' => ['nullable', 'string', 'max:200'],
            'no_incluye_items' => ['nullable', 'array'],
            'no_incluye_items.*' => ['nullable', 'string', 'max:200'],
            'imagenes_nuevas' => ['nullable', 'array', 'max:'.self::MAX_IMAGENES],
            'imagenes_nuevas.*' => $imgs,
            'imagenes_orden' => ['nullable', 'array'],
            'itinerario' => ['nullable', 'array', 'max:40'],
            'itinerario.*.titulo' => ['nullable', 'string', 'max:150'],
            'itinerario.*.horario' => ['nullable', 'string', 'max:60'],
            'itinerario.*.descripcion' => ['nullable', 'string', 'max:5000'],
            'itinerario.*.tr.*.*' => ['nullable', 'string', 'max:5000'],
            'itinerario.*.imagenes_nuevas' => ['nullable', 'array', 'max:'.self::MAX_IMAGENES_PASO],
            'itinerario.*.imagenes_nuevas.*' => $imgs,
            'precios' => ['nullable', 'array', 'max:20'],
            'precios.*.etiqueta' => ['nullable', 'string', 'max:120'],
            'precios.*.precio' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'precios.*.descripcion' => ['nullable', 'string', 'max:1000'],
            'precios.*.tr.*.*' => ['nullable', 'string', 'max:1000'],
            'descuento' => ['nullable', 'integer', 'min:0', 'max:90'],
            'secciones' => ['nullable', 'array'],
            'secciones.*' => ['integer', 'exists:secciones,id'],
            'meta_descripcion' => ['nullable', 'string', 'max:300'],
            'palabras_clave' => ['nullable', 'string', 'max:255'],
            'traducciones' => ['nullable', 'array'],
            'traducciones.*.*' => ['nullable', 'string', 'max:10000'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'Tour: escribe al menos el nombre (también para guardar un borrador).',
            'slug.regex' => 'Tour: la URL solo puede tener minúsculas, números y guiones (ej: machu-picchu-2-dias).',
            'slug.unique' => 'Tour: esa URL ya la usa otro tour. Cámbiala o déjala vacía para generarla sola.',
            'imagenes_nuevas.*.max' => 'Una imagen del tour pesa más de '.self::MAX_KB.' KB.',
            'imagenes_nuevas.*.image' => 'Los archivos del tour deben ser imágenes (JPG, PNG o WebP).',
            'imagenes_nuevas.*.mimes' => 'Las imágenes deben ser JPG, PNG o WebP.',
            'imagenes_nuevas.max' => 'Tour: máximo '.self::MAX_IMAGENES.' imágenes.',
            'itinerario.*.imagenes_nuevas.*.max' => 'Itinerario: una imagen pesa más de '.self::MAX_KB.' KB.',
            'itinerario.*.imagenes_nuevas.*.image' => 'Itinerario: los archivos deben ser imágenes.',
            'itinerario.*.imagenes_nuevas.max' => 'Itinerario: máximo '.self::MAX_IMAGENES_PASO.' imágenes por paso.',
            'precios.*.precio.numeric' => 'Precio: escribe solo números en los montos (US$).',
            'descuento.max' => 'Precio: el descuento no puede pasar de 90%.',
            'video_url.url' => 'Tour: el enlace del video no es válido.',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  Utilidades                                                         */
    /* ------------------------------------------------------------------ */

    /** @return UploadedFile[] */
    private function archivos($valor): array
    {
        return array_values(array_filter(is_array($valor) ? $valor : [], fn ($f) => $f instanceof UploadedFile && $f->isValid()));
    }

    /**
     * Convierte la lista ordenada del formulario ("old:ruta" | "new:N") en rutas.
     * Los archivos nuevos quedan como marcadores UploadedFile hasta guardar de verdad.
     */
    private function resolverOrden(array $orden, array $conocidas, array $archivos): array
    {
        $resultado = [];
        $usados = [];
        foreach ($orden as $token) {
            $token = (string) $token;
            if (str_starts_with($token, 'old:')) {
                $ruta = substr($token, 4);
                if (in_array($ruta, $conocidas, true) && ! in_array($ruta, $resultado, true)) {
                    $resultado[] = $ruta;
                }
            } elseif (str_starts_with($token, 'new:')) {
                $n = (int) substr($token, 4);
                if (isset($archivos[$n]) && ! isset($usados[$n])) {
                    $resultado[] = $archivos[$n];
                    $usados[$n] = true;
                }
            }
        }
        // Archivos que llegaron pero no figuran en el orden: al final
        foreach ($archivos as $n => $f) {
            if (! isset($usados[$n])) {
                $resultado[] = $f;
            }
        }

        return $resultado;
    }

    /** Sube los UploadedFile pendientes y devuelve solo rutas (string). */
    private function subirMarcadores(array $items, array &$archivosNuevos): array
    {
        return array_map(function ($item) use (&$archivosNuevos) {
            if (! $item instanceof UploadedFile) {
                return $item;
            }
            File::ensureDirectoryExists(public_path(self::CARPETA));
            $nombre = Str::random(24).'.'.strtolower($item->guessExtension() ?: $item->getClientOriginalExtension() ?: 'jpg');
            $item->move(public_path(self::CARPETA), $nombre);
            $ruta = self::CARPETA.'/'.$nombre;
            $archivosNuevos[] = $ruta;

            return $ruta;
        }, $items);
    }

    private function listaATexto($items): ?string
    {
        $limpio = collect((array) $items)->map(fn ($v) => trim((string) $v))->filter()->values();

        return $limpio->isNotEmpty() ? $limpio->implode("\n") : null;
    }

    /** Traducciones es/en: se actualizan los campos del formulario y se conservan las demás claves. */
    private function traducciones(Request $request, Tour $tour): ?array
    {
        $actual = $tour->traducciones ?: [];
        foreach (['es', 'en'] as $lang) {
            foreach (['nombre', 'descripcion', 'duracion', 'incluye', 'no_incluye'] as $campo) {
                if ($request->has("traducciones.$lang.$campo")) {
                    $valor = trim((string) $request->input("traducciones.$lang.$campo"));
                    if ($valor === '') {
                        unset($actual[$lang][$campo]);
                    } else {
                        $actual[$lang][$campo] = $valor;
                    }
                }
            }
        }

        return array_filter($actual) ?: null;
    }

    private function slugUnico(?string $deseado, string $nombre, Tour $tour): string
    {
        $base = $deseado ?: (Str::slug($nombre) ?: 'tour');
        $slug = $base;
        $n = 2;
        while (Tour::where('slug', $slug)->when($tour->exists, fn ($q) => $q->where('id', '!=', $tour->id))->exists()) {
            if ($deseado) {
                return $deseado; // la validación ya garantizó que es único
            }
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    /** Solo se borran archivos que subió el panel (nunca los de public/img). */
    private function borrarArchivo(?string $ruta): void
    {
        if ($ruta && str_starts_with($ruta, self::CARPETA.'/') && ! str_contains($ruta, '..')) {
            File::delete(public_path($ruta));
        }
    }

    private function copiarArchivo(string $ruta): string
    {
        $origen = public_path($ruta);
        if (! str_starts_with($ruta, self::CARPETA.'/') || ! File::exists($origen)) {
            return $ruta; // imágenes de public/img o enlaces externos se comparten tal cual
        }
        $nombre = Str::random(24).'.'.pathinfo($ruta, PATHINFO_EXTENSION);
        File::copy($origen, public_path(self::CARPETA.'/'.$nombre));

        return self::CARPETA.'/'.$nombre;
    }
}
