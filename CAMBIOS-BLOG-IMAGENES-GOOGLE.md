# Qué se agregó (noviembre 2026)

## Pasos para activarlo (en este orden)
```bash
# 1) Copia estos archivos encima de tu proyecto (mismas rutas) y luego:
php artisan migrate                 # crea la tabla del blog
php artisan config:clear && php artisan view:clear && php artisan route:clear
php artisan mayo:imagenes           # SOLO muestra el plan para tus imágenes (no cambia nada)
php artisan mayo:imagenes --aplicar # optimiza, corrige nombres, actualiza la BD y guarda respaldo
```
Luego borra `public/robots.txt` si existe (ahora es dinámico) y sube a GitHub. En Hostinger: `git pull`, `php artisan migrate`.

## Blog y novedades
- Público: `/blog` (destacado, categorías Novedades/Avisos/Guías/Consejos, búsqueda, paginación, sección **Viajeros** con
  comentarios y fotos reales + botón para dejar el suyo) y `/blog/{url}` (compartir, CTA a WhatsApp, relacionados, datos para Google).
- Panel: **Admin → Blog y novedades**: editor con formato, portada (se reduce sola), **programar publicación**, borrador,
  destacar, vista previa y **traducción automática PT→ES/EN** (usa la misma clave de traductor de los tours).
- Trilingüe (pt/es/en), igual que los tours. Link "Blog" en el menú y el footer.

## Fotos de pasajeros pesadas, sin pesar en la página
1. El navegador reduce la foto **antes de subirla** (prueba real: 9 MB → 275 KB en WebP).
2. El servidor la **vuelve a reducir** (máx. 1600 px, WebP), corrige la orientación del celular, borra metadatos (GPS) y crea una **miniatura**.
3. Las listas usan la miniatura; la foto grande **solo se descarga al tocarla** (lightbox).
También se optimizan las fotos que subes en Tours y las portadas del blog.

## Velocidad (lazy y más)
Imágenes del inicio/detalle con `loading="lazy"`/`decoding="async"` y la principal con prioridad; el **video del inicio ya no se
descarga** en celulares, con "ahorro de datos" o conexión lenta, y en PC solo después de cargar la página; compresión y caché
en `.htaccess`; CSS/JS con versión automática (`?v=`); logo del footer en diferido.

## Google y monitoreo
Ver `GOOGLE-MONITOREO.md`. Panel **Monitoreo y Google** (visitas, páginas top, países, WhatsApp, PageSpeed, salud del servidor,
imágenes pesadas) y tarjetas nuevas en el Resumen. `sitemap.xml` y `robots.txt` automáticos.

## Corregido de paso
- `admin/resenas.blade.php` cerraba un `@foreach` con `@endfor` (error al aparecer una opinión con foto).
- `.gitignore` bloqueaba `public/img/pag web.mp4`: por eso el video no subía a GitHub.
