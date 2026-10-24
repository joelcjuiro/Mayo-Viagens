<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['pt', 'es', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            // Primera visita: usa el idioma del navegador si lo soportamos; si no, portugués.
            $locale = $request->getPreferredLanguage(self::SUPPORTED) ?: 'pt';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
