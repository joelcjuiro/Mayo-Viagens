<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Conexión con Google para el panel de monitoreo:
 *  - Google Analytics 4 (Data API): visitas, páginas más vistas, países, canales y clics a WhatsApp.
 *  - PageSpeed Insights: velocidad y Core Web Vitals del sitio publicado.
 * Todo falla "suave": si algo no está configurado o Google no responde, devuelve un estado, nunca rompe el panel.
 */
class Google
{
    public static function analyticsId(): string
    {
        return trim((string) config('mayo.google.analytics_id'));
    }

    public static function analyticsIdValido(): bool
    {
        return (bool) preg_match('/^G-[A-Z0-9]{4,}$/', self::analyticsId());
    }

    public static function verificado(): bool
    {
        return filled(config('mayo.google.site_verification'));
    }

    public static function propiedad(): string
    {
        return preg_replace('/\D/', '', (string) config('mayo.google.property_id'));
    }

    /** Datos de la cuenta de servicio (JSON de Google Cloud) guardado en storage/app/google/. */
    public static function credenciales(): ?array
    {
        $ruta = storage_path('app/'.ltrim((string) config('mayo.google.service_account'), '/'));
        if (! is_file($ruta)) {
            return null;
        }
        $j = json_decode((string) file_get_contents($ruta), true);

        return (is_array($j) && ! empty($j['client_email']) && ! empty($j['private_key'])) ? $j : null;
    }

    public static function apiConectada(): bool
    {
        return self::propiedad() !== '' && self::credenciales() !== null;
    }

    private static function b64(string $d): string
    {
        return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
    }

    private static function token(array $cred): string
    {
        return Cache::remember('mayo.google.token', 3000, function () use ($cred) {
            $ahora = time();
            $cab = self::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $cuerpo = self::b64(json_encode([
                'iss' => $cred['client_email'],
                'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $ahora,
                'exp' => $ahora + 3600,
            ]));
            $firma = '';
            if (! openssl_sign("$cab.$cuerpo", $firma, $cred['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new \RuntimeException('No se pudo firmar con la clave de la cuenta de servicio. ¿El archivo JSON está completo?');
            }
            $r = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => "$cab.$cuerpo.".self::b64($firma),
            ]);
            if ($r->failed() || ! $r->json('access_token')) {
                throw new \RuntimeException('Google rechazó la cuenta de servicio: '.($r->json('error_description') ?: $r->json('error') ?: 'HTTP '.$r->status()));
            }

            return $r->json('access_token');
        });
    }

    private static function informe(string $token, array $cuerpo): array
    {
        $r = Http::withToken($token)->acceptJson()->timeout(20)
            ->post('https://analyticsdata.googleapis.com/v1beta/properties/'.self::propiedad().':runReport', $cuerpo);
        if ($r->failed()) {
            $msg = $r->json('error.message') ?: 'HTTP '.$r->status();
            if ($r->status() === 403) {
                $msg .= ' — agrega el correo de la cuenta de servicio como "Lector" en Google Analytics (Administrar → Acceso a la propiedad).';
            }
            throw new \RuntimeException($msg);
        }

        return $r->json('rows') ?? [];
    }

    private static function num($fila, int $i = 0): float
    {
        return (float) ($fila['metricValues'][$i]['value'] ?? 0);
    }

    /**
     * Resumen de Analytics de los últimos $dias días (se guarda 30 min).
     *
     * @return array{estado:string, ...}
     */
    public static function reporte(int $dias = 28, bool $forzar = false): array
    {
        if (! self::apiConectada()) {
            return ['estado' => 'sin_configurar'];
        }
        $clave = "mayo.ga.$dias";
        if ($forzar) {
            Cache::forget($clave);
            Cache::forget('mayo.google.token');
        }
        if ($hit = Cache::get($clave)) {
            return $hit;
        }

        try {
            $t = self::token(self::credenciales());
            $rango = [['startDate' => "{$dias}daysAgo", 'endDate' => 'today']];

            $tot = self::informe($t, [
                'dateRanges' => [$rango[0], ['startDate' => (2 * $dias).'daysAgo', 'endDate' => ($dias + 1).'daysAgo']],
                'metrics' => [['name' => 'activeUsers'], ['name' => 'sessions'], ['name' => 'screenPageViews'], ['name' => 'engagementRate']],
            ]);
            $actual = $previo = [0, 0, 0, 0];
            foreach ($tot as $i => $fila) {
                $etq = $fila['dimensionValues'][0]['value'] ?? ('date_range_'.$i);
                $vals = array_map(fn ($k) => self::num($fila, $k), [0, 1, 2, 3]);
                if ($etq === 'date_range_1') {
                    $previo = $vals;
                } elseif ($etq === 'date_range_0') {
                    $actual = $vals;
                }
            }

            $diario = [];
            foreach (self::informe($t, [
                'dateRanges' => $rango,
                'dimensions' => [['name' => 'date']],
                'metrics' => [['name' => 'activeUsers']],
                'orderBys' => [['dimension' => ['dimensionName' => 'date']]],
            ]) as $f) {
                $diario[] = ['fecha' => $f['dimensionValues'][0]['value'], 'v' => (int) self::num($f)];
            }

            $lista = function (string $dim, string $met, int $limite) use ($t, $rango) {
                return array_map(fn ($f) => ['n' => $f['dimensionValues'][0]['value'], 'v' => (int) self::num($f)], self::informe($t, [
                    'dateRanges' => $rango,
                    'dimensions' => [['name' => $dim]],
                    'metrics' => [['name' => $met]],
                    'orderBys' => [['metric' => ['metricName' => $met], 'desc' => true]],
                    'limit' => $limite,
                ]));
            };

            $eventos = [];
            foreach (self::informe($t, [
                'dateRanges' => $rango,
                'dimensions' => [['name' => 'eventName']],
                'metrics' => [['name' => 'eventCount']],
                'dimensionFilter' => ['filter' => ['fieldName' => 'eventName', 'inListFilter' => ['values' => ['whatsapp_click', 'generate_lead', 'review_submit']]]],
            ]) as $f) {
                $eventos[$f['dimensionValues'][0]['value']] = (int) self::num($f);
            }

            $res = [
                'estado' => 'ok',
                'dias' => $dias,
                'actual' => ['usuarios' => (int) $actual[0], 'sesiones' => (int) $actual[1], 'vistas' => (int) $actual[2], 'interaccion' => round($actual[3] * 100)],
                'previo' => ['usuarios' => (int) $previo[0], 'sesiones' => (int) $previo[1], 'vistas' => (int) $previo[2]],
                'diario' => $diario,
                'paginas' => $lista('pagePath', 'screenPageViews', 8),
                'canales' => $lista('sessionDefaultChannelGroup', 'sessions', 6),
                'paises' => $lista('country', 'activeUsers', 6),
                'eventos' => $eventos,
                'hora' => now()->toDateTimeString(),
            ];
            Cache::put($clave, $res, 1800);

            return $res;
        } catch (\Throwable $e) {
            Cache::forget('mayo.google.token');

            return ['estado' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    /** Lo último guardado en caché, sin llamar a Google (para el resumen del dashboard). */
    public static function reporteEnCache(int $dias = 28): ?array
    {
        $r = Cache::get("mayo.ga.$dias");

        return is_array($r) && ($r['estado'] ?? '') === 'ok' ? $r : null;
    }

    /** Resultado de PageSpeed Insights (se guarda 12 horas). */
    public static function pagespeed(string $estrategia = 'mobile', bool $forzar = false): array
    {
        $url = url('/');
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || preg_match('/\.(test|local|localhost)$/', $host)) {
            return ['estado' => 'local', 'mensaje' => 'PageSpeed solo puede medir un sitio publicado en internet. Pruébalo cuando esté en tu dominio.'];
        }

        $clave = 'mayo.psi.'.$estrategia;
        if (! $forzar && ($c = Cache::get($clave))) {
            return $c;
        }

        try {
            $q = http_build_query(['url' => $url, 'strategy' => $estrategia, 'locale' => 'es'])
                .'&category=performance&category=seo&category=accessibility&category=best-practices';
            if (filled(config('mayo.google.pagespeed_key'))) {
                $q .= '&key='.urlencode((string) config('mayo.google.pagespeed_key'));
            }
            $r = Http::acceptJson()->timeout(80)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed?'.$q);
            if ($r->failed()) {
                $m = $r->json('error.message') ?: 'HTTP '.$r->status();
                if ($r->status() === 429) {
                    $m = 'Google limitó las consultas gratuitas. Agrega GOOGLE_PAGESPEED_KEY en el .env o prueba en unos minutos.';
                }
                throw new \RuntimeException($m);
            }
            $l = $r->json('lighthouseResult') ?? [];
            $cat = fn ($k) => isset($l['categories'][$k]['score']) ? (int) round($l['categories'][$k]['score'] * 100) : null;
            $aud = fn ($k) => $l['audits'][$k]['displayValue'] ?? null;

            $mejoras = [];
            foreach ($l['audits'] ?? [] as $a) {
                if (($a['details']['type'] ?? '') === 'opportunity' && ($a['details']['overallSavingsMs'] ?? 0) > 150) {
                    $mejoras[] = ['t' => $a['title'] ?? '', 'v' => $a['displayValue'] ?? '', 'ms' => $a['details']['overallSavingsMs']];
                }
            }
            usort($mejoras, fn ($a, $b) => $b['ms'] <=> $a['ms']);

            $res = [
                'estado' => 'ok',
                'estrategia' => $estrategia,
                'url' => $url,
                'rendimiento' => $cat('performance'),
                'seo' => $cat('seo'),
                'accesibilidad' => $cat('accessibility'),
                'buenas' => $cat('best-practices'),
                'lcp' => $aud('largest-contentful-paint'),
                'cls' => $aud('cumulative-layout-shift'),
                'tbt' => $aud('total-blocking-time'),
                'fcp' => $aud('first-contentful-paint'),
                'peso' => $aud('total-byte-weight'),
                'real' => $r->json('loadingExperience.overall_category'),
                'mejoras' => array_slice($mejoras, 0, 5),
                'hora' => now()->toDateTimeString(),
            ];
            Cache::put($clave, $res, 43200);

            return $res;
        } catch (\Throwable $e) {
            return ['estado' => 'error', 'mensaje' => $e->getMessage()];
        }
    }

    public static function pagespeedEnCache(string $estrategia = 'mobile'): ?array
    {
        $r = Cache::get('mayo.psi.'.$estrategia);

        return is_array($r) && ($r['estado'] ?? '') === 'ok' ? $r : null;
    }

    /** Lista de chequeos de la parte Google: [clave, título, ok, detalle, cómo arreglarlo]. */
    public static function checklist(): array
    {
        return [
            ['ga', 'Google Analytics 4 instalado', self::analyticsIdValido(),
                self::analyticsIdValido() ? 'ID '.self::analyticsId().' (se activa cuando el visitante acepta cookies).' : 'Falta GOOGLE_ANALYTICS_ID en el archivo .env (empieza con G-).',
                'Crea la propiedad en analytics.google.com → Administrar → Flujos de datos → Web, y copia el "ID de medición".'],
            ['gsc', 'Google Search Console verificado', self::verificado(),
                self::verificado() ? 'Etiqueta de verificación presente en el sitio.' : 'Falta GOOGLE_SITE_VERIFICATION en el .env.',
                'En search.google.com/search-console agrega tu dominio, elige "Etiqueta HTML" y copia solo el valor de content="…".'],
            ['sitemap', 'Mapa del sitio (sitemap.xml) listo', true,
                url('/sitemap.xml').' — incluye tours y artículos del blog.',
                'Envíalo en Search Console → Sitemaps.'],
            ['api', 'Visitas visibles dentro de este panel', self::apiConectada(),
                self::apiConectada() ? 'Cuenta de servicio conectada a la propiedad '.self::propiedad().'.' : 'Falta GOOGLE_ANALYTICS_PROPERTY_ID y el archivo de la cuenta de servicio.',
                'Sigue la guía GOOGLE-MONITOREO.md (10 minutos, una sola vez).'],
        ];
    }
}
