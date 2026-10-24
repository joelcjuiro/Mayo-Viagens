# Mayo Viagens: cómo instalar estos archivos

1. Copia el contenido de esta carpeta sobre tu proyecto `C:\xampp\htdocs\mayo-viagens`
   (acepta reemplazar: `layouts/app`, `inicio`, `login`, `Tour.php`, `AdminController.php`,
   `routes/web.php`, `DatabaseSeeder.php`, `style.css` y `main.js`).
2. Borra `resources/views/destinos.blade.php` (está vacío y no se usa).
3. En una terminal dentro del proyecto:

```
php artisan migrate
php artisan db:seed
php artisan config:clear
php artisan view:clear
php artisan serve
```

4. Abre http://localhost:8000
   - Panel: http://localhost:8000/login
   - Usuario: `admin@mayoviagens.com`  Contraseña: `Mayo2026!`  (cámbiala después de entrar)

`php artisan migrate` crea las tablas que faltaban (tu log mostraba `no such table: tours`).
`php artisan db:seed` crea el admin y 5 tours de ejemplo; puedes ejecutarlo de nuevo sin duplicar.

## Imágenes (pon tus fotos en `public/img/`)
- `hero.jpg`: la foto grande del home (si no está, se ve el degradado azul)
- `tours/city-tour.jpg`, `vale-sagrado.jpg`, `machu-picchu.jpg`, `montanha-7-cores.jpg`, `humantay.jpg`
- `logo.svg` y `logo-footer.svg` son logos provisionales: reemplázalos por los tuyos
  (o cambia las rutas en `partials/header` y `partials/footer`).

## Qué cambié en tu backend
- `AdminController`: agregué `updateReserva` (tu ruta PATCH existía pero el método no),
  `updateContacto` (marcar mensaje leído), filtro por estado, y datos extra para el resumen.
- `routes/web.php`: una sola ruta nueva, `PATCH /admin/contactos/{id}`.
- `Tour.php`: accesores `imagen_url`, `resumen` e `icono`; quité `recomendaciones`
  del `$fillable` porque esa columna no existe en la migración.

## Novedades v3

### Idiomas (PT / ES / EN)
- Selector funcional en el header (`/idioma/pt|es|en`). Primera visita: usa el idioma del navegador; si no es pt/es/en, portugués.
- Textos del sitio: `lang/pt/site.php`, `lang/es/site.php`, `lang/en/site.php`.
- Los tours guardan su traducción en la columna nueva `traducciones` (JSON). `db:seed` carga es/en de los 5 tours de ejemplo.
  Un tour sin traducción se muestra en portugués (idioma base).
- Pendiente: los mensajes de éxito/validación de `ReservaController` y `ContactoController` (no estaban en el zip) siguen en español.
  Para traducirlos usa `__('site....')` en esos controladores.
- El panel admin queda en español.

### Datos reales de confianza: `config/mayo.php` + `.env`
El sitio NO inventa cifras. Estos bloques aparecen solo si pones datos reales:
```
MAYO_EMAIL=contacto@tudominio.com
MAYO_RATING=4.8
MAYO_RATING_COUNT=120
MAYO_SINCE=2018
MAYO_INSTAGRAM=https://instagram.com/tuagencia
MAYO_FACEBOOK=https://facebook.com/tuagencia
```
- Reseñas: se cargan en `config/mayo.php` → `reviews` (con permiso del cliente y, si puedes, el enlace a la reseña original).
- "Viajeros" se calcula con las reservas confirmadas del panel (o `MAYO_TRAVELERS`).
- Foto del hero: `public/img/hero.jpg`. Sin foto se ve un degradado.

### Panel (dashboard)
Ingresos estimados, comparación con el periodo anterior, tasa de confirmación, ticket promedio, tiempo de atención,
alertas, próximas salidas, estado de reservas, actividad reciente, "Confianza del cliente" (checklist) y exportar reservas a CSV.
