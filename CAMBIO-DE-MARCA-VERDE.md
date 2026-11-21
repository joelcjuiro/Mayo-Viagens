# Cambio de marca: logo verde

- **Logo nuevo** en `public/img/logo.webp` (26 KB) y `logo.png` (15 KB, respaldo). El menú usa el WebP; el footer lo muestra sobre una placa blanca.
- **Paleta**: de negro cálido + ámbar a **verde bosque + verde marca + lima** (sale de los verdes del logo). Se cambió en toda la web pública
  y en el panel. Las **estrellas siguen amarillas**; advertencias (ámbar) y errores (rojo) del panel se mantienen.
- **Favicon** y `apple-touch-icon.png` (ícono de la montaña) y **imagen para compartir** `og.jpg` (WhatsApp/Facebook) nuevos.
- Para cambiar la paleta de nuevo: variables de `:root` al inicio de `public/css/theme.css` y `public/css/admin-tour.css`.

## Después de subir
1. Borra el logo viejo: `git rm public/img/logo.jpeg` (ya no se usa).
2. Facebook/WhatsApp guardan la imagen anterior: pega tu dominio en https://developers.facebook.com/tools/debug/ y pulsa «Volver a extraer».
3. Ctrl+F5 en el navegador (el favicon puede tardar en cambiar).
