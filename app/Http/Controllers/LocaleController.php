<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;

class LocaleController extends Controller
{
    public function cambiar(string $lang)
    {
        if (in_array($lang, SetLocale::SUPPORTED, true)) {
            session(['locale' => $lang]);
        }

        $prev = url()->previous();

        return redirect(str_starts_with($prev, url('/')) ? $prev : route('inicio'));
    }
}
