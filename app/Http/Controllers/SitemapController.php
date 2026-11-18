<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Tour;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** sitemap.xml y robots.txt para Google (Search Console los lee). */
class SitemapController extends Controller
{
    public function xml()
    {
        $xml = Cache::remember('mayo.sitemap', 3600, function () {
            $urls = [];
            $add = function (string $loc, $lastmod = null, string $freq = 'monthly', string $prio = '0.6') use (&$urls) {
                $urls[] = compact('loc', 'lastmod', 'freq', 'prio');
            };

            $add(route('inicio'), null, 'weekly', '1.0');
            $add(route('tours'), null, 'weekly', '0.9');
            foreach (rescue(fn () => Tour::publicos()->get(['slug', 'updated_at']), collect(), false) as $t) {
                $add(route('tour.detalle', $t->slug), $t->updated_at, 'monthly', '0.8');
            }

            if (Schema::hasTable('articulos')) {
                $add(route('blog'), null, 'weekly', '0.7');
                foreach (Articulo::publicados()->get(['slug', 'updated_at']) as $a) {
                    $add(route('blog.show', $a->slug), $a->updated_at, 'monthly', '0.7');
                }
            }

            foreach (['nosotros' => '0.6', 'opiniones' => '0.6', 'contacto' => '0.6', 'reservas' => '0.6', 'esnna' => '0.4', 'privacidad' => '0.3', 'terminos' => '0.3'] as $r => $p) {
                $add(route($r), null, 'monthly', $p);
            }

            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
            foreach ($urls as $u) {
                $out .= '  <url><loc>'.e($u['loc']).'</loc>'
                    .($u['lastmod'] ? '<lastmod>'.$u['lastmod']->toAtomString().'</lastmod>' : '')
                    .'<changefreq>'.$u['freq'].'</changefreq><priority>'.$u['prio'].'</priority></url>'."\n";
            }

            return $out.'</urlset>'."\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots()
    {
        $txt = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /login\nDisallow: /idioma/\n\nSitemap: ".route('sitemap')."\n";

        return response($txt, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
