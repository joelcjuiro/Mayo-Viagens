# Mayo Viagens — guía de instalación y uso

## 1. Instalar esta actualización
1. Copia los archivos sobre tu proyecto (respeta las carpetas) y acepta reemplazar.
2. En la terminal del proyecto (usa el PHP de XAMPP):
```
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan serve --port=8080
```
3. Abre http://localhost:8080 y refresca con Ctrl+F5.

`migrate` aplica varias migraciones nuevas (editor de tours, traducciones, libro de reclamaciones,
destacado, opiniones, fotos de opiniones y roles de usuario). Si algo falla, mira la última parte del error con:
```
powershell -c "Get-Content storage\logs\laravel.log -Tail 40"
```

## 2. Usuarios y permisos del panel
Menú **Usuarios** (solo administradores): crea usuarios con correo y contraseña.
- **Administrador**: todo, incluido eliminar contenido y gestionar usuarios.
- **Editor**: crea/edita tours y secciones, modera opiniones, atiende reservas, mensajes y reclamos. No elimina nada ni administra usuarios.
Los usuarios que ya existían quedan como administradores.

## 3. Traducción automática de tours
Panel **Idiomas** del editor → botón «Traducir a los demás idiomas». Escribes el tour una vez y se traduce al portugués, español e inglés
(nombre, duración, descripción, incluye/no incluye, itinerario y precios).
Para activarlo hace falta una clave (gratis hasta cierto uso). En tu archivo `.env` agrega:
```
TRADUCTOR_PROVIDER=deepl
TRADUCTOR_KEY=tu_clave_aqui
```
- DeepL API Free: https://www.deepl.com/pro-api (la clave termina en `:fx`).
- O Google Cloud Translation: `TRADUCTOR_PROVIDER=google` y tu API key.
Luego: `C:\xampp\php\php.exe artisan config:clear`.

## 4. Otras configuraciones
- Colores del sitio: `public/css/theme.css` (variables al inicio).
- Imágenes subidas: `public/uploads/` (no necesitas `storage:link`).
- Foto de «Movilidad»: `public/img/transporte.jpg`. Imagen al compartir en redes: `public/img/og.jpg`.
- Sello ESNNA: `public/img/esnna.png` (o su nombre en `LEGAL_ESNNA_IMAGE` del `.env`).
- Datos legales: `LEGAL_RUC` y `LEGAL_EMAIL` en el `.env`.
- Más velocidad en local: `SESSION_DRIVER=file` y `CACHE_STORE=file` en el `.env`.
