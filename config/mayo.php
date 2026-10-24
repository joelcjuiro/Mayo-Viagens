<?php

/*
|--------------------------------------------------------------------------
| Datos públicos de Mayo Viagens
|--------------------------------------------------------------------------
| Los bloques de confianza (valoración, reseñas, años de experiencia) solo se
| muestran en el sitio cuando tienen datos REALES. Déjalos vacíos hasta tenerlos.
| Puedes definirlos en .env o editarlos aquí.
*/

return [
    'whatsapp' => env('MAYO_WHATSAPP', '51944845870'),
    'whatsapp_display' => env('MAYO_WHATSAPP_DISPLAY', '+51 944 845 870'),
    'email' => env('MAYO_EMAIL'),                       // ej: contacto@tudominio.com
    'address' => env('MAYO_ADDRESS', 'Cusco - Peru'),

    'social' => [
        'facebook' => env('MAYO_FACEBOOK'),
        'instagram' => env('MAYO_INSTAGRAM'),
        'youtube' => env('MAYO_YOUTUBE'),
        'tripadvisor' => env('MAYO_TRIPADVISOR'),
    ],

    // Valoración promedio real (ej: 4.8) y cantidad de reseñas en que se basa.
    'rating' => env('MAYO_RATING'),
    'rating_count' => env('MAYO_RATING_COUNT'),

    // Año en que empezó la agencia (ej: 2018). Calcula "años de experiencia".
    'since' => env('MAYO_SINCE'),

    // Viajeros atendidos. Si es null, se calcula con las reservas confirmadas del panel.
    'travelers' => env('MAYO_TRAVELERS'),

    // Reseñas reales de clientes (con su permiso). Ejemplo de formato:
    // ['name' => 'Ana P.', 'country' => 'Brasil', 'date' => '2026-08-14', 'stars' => 5,
    //  'text' => 'Texto de la reseña…', 'url' => 'https://enlace-a-la-reseña-original']
    'reviews' => [],
];
