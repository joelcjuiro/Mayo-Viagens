<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Traducción automática (DeepL o Google Cloud Translation).
 * Configura en .env:  TRADUCTOR_PROVIDER=deepl  y  TRADUCTOR_KEY=tu_clave
 */
class Traductor
{
    public const IDIOMAS = ['pt', 'es', 'en'];

    public static function disponible(): bool
    {
        return filled(config('services.traductor.key'));
    }

    /**
     * @param  array<string,string>  $textos  [id => texto]
     * @param  string[]  $destinos  ej: ['pt','en']
     * @return array<string,array<string,string>>  [id => [idioma => traducción]]
     */
    public static function traducir(array $textos, string $origen, array $destinos): array
    {
        if (! self::disponible()) {
            throw new \RuntimeException('La traducción automática no está configurada. Agrega TRADUCTOR_KEY en el archivo .env.');
        }

        $ids = array_keys($textos);
        $resultado = array_fill_keys($ids, []);

        foreach ($destinos as $destino) {
            if ($destino === $origen || ! in_array($destino, self::IDIOMAS, true)) {
                continue;
            }
            foreach (array_chunk($ids, 40) as $grupo) {
                $lote = array_map(fn ($id) => $textos[$id], $grupo);
                $traducidos = config('services.traductor.provider') === 'google'
                    ? self::google($lote, $origen, $destino)
                    : self::deepl($lote, $origen, $destino);

                foreach ($grupo as $i => $id) {
                    $resultado[$id][$destino] = $traducidos[$i] ?? '';
                }
            }
        }

        return $resultado;
    }

    private static function deepl(array $lote, string $origen, string $destino): array
    {
        $key = (string) config('services.traductor.key');
        $base = str_ends_with($key, ':fx') ? 'https://api-free.deepl.com' : 'https://api.deepl.com';
        $codigos = ['es' => 'ES', 'en' => 'EN-US', 'pt' => 'PT-BR'];

        $resp = Http::timeout(40)
            ->withHeaders(['Authorization' => 'DeepL-Auth-Key '.$key])
            ->asJson()
            ->post($base.'/v2/translate', [
                'text' => array_values($lote),
                'source_lang' => strtoupper($origen),
                'target_lang' => $codigos[$destino],
                'preserve_formatting' => true,
            ]);

        if ($resp->failed()) {
            throw new \RuntimeException('DeepL respondió con error '.$resp->status().($resp->status() === 403 ? ' (revisa tu clave TRADUCTOR_KEY)' : '').'.');
        }

        return array_map(fn ($t) => $t['text'] ?? '', $resp->json('translations', []));
    }

    private static function google(array $lote, string $origen, string $destino): array
    {
        $resp = Http::timeout(40)->asJson()->post(
            'https://translation.googleapis.com/language/translate/v2?key='.urlencode((string) config('services.traductor.key')),
            ['q' => array_values($lote), 'source' => $origen, 'target' => $destino, 'format' => 'text']
        );

        if ($resp->failed()) {
            throw new \RuntimeException('Google Translate respondió con error '.$resp->status().' (revisa tu clave TRADUCTOR_KEY).');
        }

        return array_map(fn ($t) => html_entity_decode($t['translatedText'] ?? '', ENT_QUOTES | ENT_HTML5), $resp->json('data.translations', []));
    }
}
