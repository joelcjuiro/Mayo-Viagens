<?php

namespace App\Console\Commands;

use App\Models\Tour;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AsignarImagenesTours extends Command
{
    protected $signature = 'tours:imagenes {--force : Reemplaza también las imágenes ya asignadas}';

    protected $description = 'Asigna a cada tour una foto de public/img/tours según su nombre';

    private const ALIAS = [
        'city-tour-cusco' => ['citytour', 'city2', 'koricancha'],
        'vale-sagrado' => ['pisac', 'chinchero', 'ollantaytambo'],
        'machu-picchu' => ['machupicchu', 'aguascalientes'],
        'montanha-7-cores' => ['7colores', 'montana'],
        'laguna-humantay' => ['lagunahumantay', 'humantay', 'laguna2'],
    ];

    public function handle(): int
    {
        $dir = public_path('img/tours');

        if (! is_dir($dir)) {
            $this->error('No existe la carpeta public/img/tours');

            return self::FAILURE;
        }

        $norm = collect(scandir($dir))
            ->filter(fn ($f) => preg_match('/\.(jpe?g|png|webp)$/i', $f))
            ->mapWithKeys(fn ($f) => [$f => $this->norm(pathinfo($f, PATHINFO_FILENAME))]);

        foreach (Tour::all() as $t) {
            $actual = (string) $t->imagen;

            if (! $this->option('force') && $actual !== '') {
                if (Str::startsWith($actual, ['http://', 'https://', '//']) || is_file(public_path($actual))) {
                    $this->line("= {$t->nombre}: ya tiene imagen");

                    continue;
                }
            }

            $claves = self::ALIAS[$t->slug] ?? [$this->norm($t->slug), $this->norm($t->nombre)];
            $archivo = $this->buscar($norm, $claves);

            if (! $archivo) {
                $this->warn("? {$t->nombre}: sin coincidencia (asígnala desde el panel)");

                continue;
            }

            $t->update(['imagen' => 'img/tours/'.$archivo]);
            $this->info("✓ {$t->nombre} → {$archivo}");
        }

        return self::SUCCESS;
    }

    private function norm(string $s): string
    {
        return (string) Str::of($s)->ascii()->lower()->replaceMatches('/[^a-z0-9]/', '');
    }

    private function buscar($norm, array $claves): ?string
    {
        foreach ($claves as $c) {
            if ($c !== '' && ($f = $norm->search($c)) !== false) {
                return $f;
            }
        }

        foreach ($claves as $c) {
            if ($c === '') {
                continue;
            }
            $f = $norm->filter(fn ($n) => str_contains($n, $c) || (strlen($n) >= 5 && str_contains($c, $n)))->keys()->first();
            if ($f) {
                return $f;
            }
        }

        return null;
    }
}