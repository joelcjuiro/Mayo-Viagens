# Google para Mayo Viagens: monitoreo, visitas y velocidad

Todo es **opcional y se activa solo con variables en el `.env`**. El panel **Admin → Monitoreo y Google** te muestra
qué está listo y qué falta. Tiempo total: ~20 minutos, una sola vez.

## 1. Google Analytics 4 (visitas) — el más importante
1. Entra a https://analytics.google.com → *Administrar* → *Crear propiedad* (nombre: Mayo Viagens, zona horaria Lima).
2. *Flujos de datos* → *Web* → pon tu dominio. Copia el **ID de medición** (`G-XXXXXXXXXX`).
3. En el `.env` del servidor: `GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX`  y luego `php artisan config:clear`.

El sitio mostrará un aviso de cookies. **Google Analytics solo se carga si el visitante pulsa "Aceptar"** (no pesa ni
rastrea a quien rechaza). Ya mide automáticamente: clics a WhatsApp (`whatsapp_click`), reservas y contactos enviados
(`generate_lead`) y opiniones enviadas (`review_submit`). En Analytics márcalos como *conversiones* para verlos en reportes.

> Importante: actualiza tu **Política de Privacidad** para mencionar que usas Google Analytics (cookies de análisis).

## 2. Google Search Console (que te encuentren en Google)
1. https://search.google.com/search-console → *Añadir propiedad* → *Prefijo de URL* con tu dominio.
2. Método **Etiqueta HTML**: copia solo lo que está dentro de `content="..."`.
3. `.env`: `GOOGLE_SITE_VERIFICATION=ese_valor` → `php artisan config:clear` → pulsa *Verificar*.
4. *Sitemaps* → escribe `sitemap.xml` → Enviar. (Se genera solo con tus tours y artículos del blog.)

## 3. Ver las visitas DENTRO del panel (opcional)
1. https://console.cloud.google.com → crea un proyecto → *APIs y servicios* → habilita **Google Analytics Data API**.
2. *Credenciales* → *Crear cuenta de servicio* → entra a ella → *Claves* → *Agregar clave* → **JSON** (se descarga).
3. Guarda ese archivo como `storage/app/google/service-account.json` (esa carpeta **no se sube a GitHub**).
4. En Analytics → *Administrar* → *Acceso a la propiedad* → agrega el correo de la cuenta de servicio (`...iam.gserviceaccount.com`) con rol **Lector**.
5. En Analytics → *Administrar* → *Detalles de la propiedad*: copia el **ID de la propiedad** (solo números) →
   `.env`: `GOOGLE_ANALYTICS_PROPERTY_ID=123456789`.

## 4. Velocidad (PageSpeed)
Funciona sin configurar nada una vez que el sitio esté publicado: en *Monitoreo y Google* pulsa **Medir ahora**.
Si Google limita las consultas, crea una clave gratuita (Cloud Console → Credenciales → Clave de API, habilita
*PageSpeed Insights API*) y ponla en `GOOGLE_PAGESPEED_KEY`.

## 5. Lo que conviene hacer además (fuera del código)
- **Perfil de Empresa en Google (Maps)**: https://business.google.com — reseñas y llamadas directas desde Google.
- Pedir a los pasajeros que dejen su opinión también en Google Maps.
