# Subir Mayo Viagens a Hostinger (con GitHub)

Requisitos: plan con **SSH** (Business o superior), PHP 8.2+, un dominio y el proyecto en GitHub.

## A. En tu computadora
1. Copia la actualización sobre tu proyecto y súbela a GitHub:
   `git add .` → `git commit -m "Listo para Hostinger"` → `git push`
2. Comprueba que en GitHub NO esté el archivo `.env` (está ignorado) y que SÍ estén `public/img` y `public/css`.

## B. En hPanel (panel de Hostinger)
1. **Sitios web → Administrar → Avanzado → Configuración de PHP**: versión **8.2 o superior** (8.3 recomendado).
   Extensiones activas: pdo_mysql, mbstring, openssl, fileinfo, curl, zip, gd.
2. **Bases de datos → MySQL**: crea una base de datos + usuario. Anota: nombre, usuario y contraseña.
3. **Seguridad → SSL**: activa el certificado gratis y "Forzar HTTPS".
4. **Avanzado → Acceso SSH**: actívalo y anota host, puerto (suele ser 65002) y usuario (u123456789).

## C. Por SSH (PowerShell o la terminal de hPanel)
```
ssh -p 65002 u123456789@IP_DEL_SERVIDOR
cd ~/domains/TU-DOMINIO.com
rm -rf public_html DO_NOT_UPLOAD_HERE        # carpeta por defecto, vacía
git clone https://github.com/TU_USUARIO/TU_REPO.git .
ln -s public public_html                      # Hostinger sirve public_html; Laravel usa public
composer install --no-dev --optimize-autoloader
cp .env.hostinger.example .env
nano .env                                     # completa dominio y datos de MySQL (Ctrl+O guardar, Ctrl+X salir)
php artisan key:generate
php artisan migrate --force
php artisan mayo:admin tu-correo@dominio.com --name="Tu Nombre"   # te pide la contraseña
php artisan optimize
chmod -R 775 storage bootstrap/cache public/uploads
```
Repositorio privado: genera una llave en el servidor (`ssh-keygen -t ed25519`), copia `cat ~/.ssh/id_ed25519.pub`
y pégala en GitHub → tu repo → Settings → Deploy keys. Luego clona con `git@github.com:USUARIO/REPO.git`.

NO ejecutes `php artisan db:seed` en producción (crea un usuario de prueba con contraseña conocida).

## D. Actualizar el sitio después
Haz `git push` desde tu computadora y luego, por SSH:
```
cd ~/domains/TU-DOMINIO.com
bash deploy.sh
```

## E. Problemas frecuentes
- Página en blanco o error 500: `tail -n 40 storage/logs/laravel.log`
- "No application encryption key": falta `php artisan key:generate`.
- CSS/imagenes sin cargar: revisa que `APP_URL` sea https://tu-dominio.com y que `public_html` apunte a `public` (`ls -la`).
- `php -v` muestra una versión vieja en SSH: usa `/opt/alt/php83/usr/bin/php` en vez de `php`.
- Las fotos que subas quedan en `public/uploads/` (no se borran con `git pull`). Haz copia de esa carpeta y de la base de datos.
