<?php

// Google: monitoreo y búsqueda. Todo es opcional; el panel «Monitoreo y Google» te dice qué falta (ver GOOGLE-MONITOREO.md).
return [
    // ID de medición de Google Analytics 4 (empieza con G-). Solo se carga si el visitante acepta las cookies.
    'analytics_id' => env('GOOGLE_ANALYTICS_ID'),
    // Valor de content="..." de la etiqueta de verificación de Google Search Console.
    'site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    // Para ver las visitas dentro del panel: ID NUMÉRICO de la propiedad GA4 y una cuenta de servicio
    // (archivo JSON dentro de storage/app/google/).
    'property_id' => env('GOOGLE_ANALYTICS_PROPERTY_ID'),
    'service_account' => env('GOOGLE_SERVICE_ACCOUNT', 'google/service-account.json'),
    // Clave gratuita de PageSpeed Insights (opcional).
    'pagespeed_key' => env('GOOGLE_PAGESPEED_KEY'),
];
