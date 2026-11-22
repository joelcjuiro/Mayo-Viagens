<?php

return [
    'name' => env('LEGAL_NAME', 'Mayo Viagens'),
    'ruc' => env('LEGAL_RUC'),            // si está vacío, no se muestra
    'address' => env('LEGAL_ADDRESS', 'Cusco, Perú'),
    'email' => env('LEGAL_EMAIL'),        // si está vacío, no se muestra
    'city' => 'Cusco',
    'updated' => '2026-10-14',
    'esnna_image' => env('LEGAL_ESNNA_IMAGE'),   // nombre del archivo en public/img (ej: sello-esnna.png)

    // Política de cancelación (ajusta a la tuya)
    'days_full' => 30,   // con 30 días o más: reembolso total (menos costos ya pagados a terceros)
    'days_half' => 15,   // entre 15 y 29 días: reembolso parcial
    'pct_half' => 50,    // porcentaje del reembolso parcial
];