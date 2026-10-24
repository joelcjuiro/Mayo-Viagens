# Cambios: editor de tours, secciones, login, menús y nuevo diseño

1. Copia estos archivos sobre tu proyecto (respeta las carpetas) y acepta reemplazar.
2. En la terminal del proyecto:
```
php artisan migrate
php artisan view:clear
php artisan config:clear
```
`migrate` aplica varias migraciones nuevas (editor de tours, traducciones, libro de reclamaciones, destacado y opiniones).
3. Refresca con Ctrl+F5.

- Colores del sitio: todo está en `public/css/theme.css` (variables al inicio del archivo).
- Imágenes subidas: `public/uploads/tours/` (no necesitas `storage:link`).
- Rendimiento local: en `.env` usa `SESSION_DRIVER=file` y `CACHE_STORE=file`.
- Páginas legales: `/terminos-y-condiciones`, `/politica-de-privacidad`, `/codigo-esnna`. Sello ESNNA: guarda la imagen en `public/img/esnna.png` (o pon su nombre en `LEGAL_ESNNA_IMAGE` del `.env`).
- Libro de Reclamaciones: `/libro-de-reclamaciones` (público) y `Libro de reclamaciones` en el panel.
- Foto de la sección Movilidad: `public/img/transporte.jpg`. Imagen al compartir en redes: `public/img/og.jpg`.
- Completa `LEGAL_RUC` y `LEGAL_EMAIL` en el `.env` para que aparezcan en las páginas legales.
