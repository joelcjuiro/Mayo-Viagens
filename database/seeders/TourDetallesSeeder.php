<?php

namespace Database\Seeders;

use App\Models\Tour;
use Illuminate\Database\Seeder;

class TourDetallesSeeder extends Seeder
{
    public function run(): void
    {
        $datos = [
            'city-tour-cusco'  => ['categoria' => 'Cultural', 'nivel' => 'Baixo', 'tamano_grupo' => 15, 'badge' => null],
            'vale-sagrado'     => ['categoria' => 'Cultural', 'nivel' => 'Baixo', 'tamano_grupo' => 15, 'badge' => null],
            'machu-picchu'     => ['categoria' => 'Cultural', 'nivel' => 'Médio', 'tamano_grupo' => 12, 'badge' => 'Mais vendido'],
            'montanha-7-cores' => ['categoria' => 'Trekking', 'nivel' => 'Alto',  'tamano_grupo' => 15, 'badge' => 'Mais vendido'],
            'laguna-humantay'  => ['categoria' => 'Trekking', 'nivel' => 'Médio', 'tamano_grupo' => 15, 'badge' => null],
        ];

        foreach ($datos as $slug => $extra) {
            Tour::where('slug', $slug)->update($extra + ['destino' => 'Cusco', 'descuento' => 0]);
        }
    }
}