# Instalación en hosting cPanel

Guía para publicar Espectro CRM en un hosting compartido con cPanel, PHP y MySQL. Hay dos caminos según tu plan: **con SSH/Terminal** (más rápido) y **sin SSH** (todo desde el navegador). Lee primero la sección de preparación.

Ejemplo usado en toda la guía: usuario de cPanel `espectro`, dominio `crm.espectro.mx`, carpeta del proyecto `/home/espectro/espectro-crm`.

---

## 0. Preparación (en tu computadora)

1. Verifica en cPanel → **Select PHP Version** (o **MultiPHP Manager**) que el dominio use **PHP 8.3 o superior** y que estén activas las extensiones: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `bcmath`, `intl`.
2. En tu computadora, dentro del proyecto, instala las dependencias de producción:

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. Comprueba que existe `public/build/manifest.json` (los estilos ya compilados). Si modificaste estilos, ejecuta `npm run build`.
4. Comprime el proyecto **sin** las carpetas `node_modules` ni `.git`, y **sin** tu archivo `.env` local:

   ```bash
   zip -r espectro-crm.zip . -x "node_modules/*" ".git/*" ".env" "storage/logs/*" "database/database.sqlite"
   ```

   La carpeta `vendor` **sí** debe ir dentro del zip (así no necesitas Composer en el servidor).

---

## 1. Crear la base de datos MySQL

En cPanel → **MySQL® Databases**:

1. *Create New Database*: `espectro_crm` → cPanel la nombra `espectro_crm` con prefijo, p. ej. `espectro_espectro_crm`.
2. *Add New User*: `crmuser` con una contraseña fuerte → queda `espectro_crmuser`.
3. *Add User To Database*: elige ambos y marca **ALL PRIVILEGES**.

Anota el nombre completo de la base, del usuario y la contraseña.

---

## 2. Subir los archivos

Lo correcto es que **solo la carpeta `public` sea accesible desde Internet**. El resto (`.env`, `app`, `vendor`, `storage`) debe quedar **fuera de `public_html`**.

1. cPanel → **File Manager** → entra a tu carpeta de inicio `/home/espectro` (no a `public_html`).
2. Crea la carpeta `espectro-crm`, entra y usa **Upload** para subir `espectro-crm.zip`.
3. Clic derecho sobre el zip → **Extract**. Debe quedar `/home/espectro/espectro-crm/app`, `/home/espectro/espectro-crm/public`, etc.
4. Borra el zip.

---

## 3. Configurar la carpeta `public`

### Opción A (recomendada): subdominio o dominio apuntando a `public`

1. cPanel → **Domains** (o **Subdomains** / **Addon Domains**) → crea o edita `crm.espectro.mx`.
2. En **Document Root** escribe: `espectro-crm/public`
3. Guarda. No hay que mover ni editar nada más.

### Opción B: el dominio principal está fijo en `public_html`

Úsala solo si tu plan no permite cambiar la raíz del dominio.

1. Copia **el contenido** de `/home/espectro/espectro-crm/public` (incluido el archivo oculto `.htaccess`; activa *Settings → Show Hidden Files* en File Manager) dentro de `/home/espectro/public_html`.
2. Edita `/home/espectro/public_html/index.php` y deja estas líneas así:

   ```php
   if (file_exists($maintenance = __DIR__.'/../espectro-crm/storage/framework/maintenance.php')) {
       require $maintenance;
   }

   require __DIR__.'/../espectro-crm/vendor/autoload.php';

   /** @var Application $app */
   $app = require_once __DIR__.'/../espectro-crm/bootstrap/app.php';
   $app->usePublicPath(__DIR__);

   $app->handleRequest(Request::capture());
   ```

3. Cada vez que vuelvas a compilar estilos, copia de nuevo `public/build` a `public_html/build`.

---

## 4. Configurar `.env`

1. En File Manager, dentro de `/home/espectro/espectro-crm`, copia `.env.example` como `.env`.
2. Edítalo (clic derecho → **Edit**) con estos valores:

   ```dotenv
   APP_NAME="Espectro CRM"
   APP_ENV=production
   APP_KEY=
   APP_DEBUG=false
   APP_URL=https://crm.espectro.mx

   APP_LOCALE=es
   APP_FALLBACK_LOCALE=es
   APP_TIMEZONE=America/Monterrey

   LOG_CHANNEL=daily
   LOG_LEVEL=warning

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=espectro_espectro_crm
   DB_USERNAME=espectro_crmuser
   DB_PASSWORD=la_contraseña_de_la_base

   SESSION_DRIVER=database
   SESSION_LIFETIME=480
   SESSION_SECURE_COOKIE=true
   CACHE_STORE=database
   QUEUE_CONNECTION=sync
   MAIL_MAILER=log
   ```

3. **APP_KEY**: con SSH se genera con `php artisan key:generate`. Sin SSH, genera una en tu computadora con `php artisan key:generate --show` y pega el resultado completo (empieza con `base64:`) en `APP_KEY=`.

> `APP_DEBUG=false` es obligatorio en producción: con `true` los errores muestran contraseñas y rutas internas.
> `SESSION_SECURE_COOKIE=true` requiere HTTPS. Activa el certificado gratuito en cPanel → **SSL/TLS Status** → *Run AutoSSL*. Si aún no tienes HTTPS, pon `false` temporalmente.

---

## 5. Crear las tablas

### Con SSH o Terminal de cPanel

cPanel → **Terminal** (o conéctate por SSH):

```bash
cd ~/espectro-crm
php artisan key:generate          # solo si APP_KEY está vacío
php artisan migrate --force
php artisan db:seed --class=CatalogSeeder --force
php artisan crm:create-admin
```

> Si `php` apunta a una versión vieja, usa la ruta completa, p. ej. `/opt/cpanel/ea-php83/root/usr/bin/php artisan migrate --force`.

### Sin SSH (phpMyAdmin)

**Opción 1 — importar el SQL incluido (más simple)**

1. cPanel → **phpMyAdmin** → selecciona tu base `espectro_espectro_crm` en la columna izquierda.
2. Pestaña **Importar** → elige el archivo `database/install/instalacion.sql` del proyecto → **Continuar**.
3. Esto crea todas las tablas, los catálogos y un **administrador temporal**:
   * Correo: `admin@espectro.local`
   * Contraseña: `CambiarEsta2026`
4. Entra al sistema y de inmediato ve a **Configuración → Usuarios**: crea tu usuario administrador real y desactiva `admin@espectro.local` (o edítalo cambiando correo y contraseña).

**Opción 2 — preparar la base en tu computadora con tus datos reales**

1. En tu computadora, con MySQL local y el `.env` apuntando a una base vacía:

   ```bash
   php artisan migrate:fresh --seeder="Database\Seeders\CatalogSeeder"
   php artisan crm:create-admin --name="Tu nombre" --email="tu@correo.mx"
   mysqldump -u root -p --no-tablespaces espectro_crm > base-inicial.sql
   ```

2. En phpMyAdmin del hosting, importa `base-inicial.sql` como en la opción 1.

> Si en el futuro se agregan migraciones nuevas y no tienes SSH, regenera `database/install/instalacion.sql` (ver “Actualizaciones” abajo) o aplica las migraciones en tu computadora y exporta/importa solo las tablas nuevas.

---

## 6. Permisos de carpetas

Laravel necesita escribir en `storage` y `bootstrap/cache`.

* File Manager → clic derecho sobre `storage` → **Change Permissions** → `755` (marcar *Recurse into subdirectories* si aparece). Igual para `bootstrap/cache`.
* En la mayoría de los cPanel (PHP corre con tu usuario) `755` para carpetas y `644` para archivos es suficiente. Solo si aparece “Permission denied” en el log, usa `775` en esas dos carpetas. **Nunca** uses `777`.
* `.env` debe quedar en `640` o `600`.

Con SSH:

```bash
cd ~/espectro-crm
find storage bootstrap/cache -type d -exec chmod 755 {} \;
find storage bootstrap/cache -type f -exec chmod 644 {} \;
chmod 640 .env
```

---

## 7. Storage y `storage:link`

El CRM **no almacena archivos** (los documentos se manejan en Google Drive / iCloud), así que `storage:link` **no es necesario**. Si en el futuro se agregan archivos públicos:

* Con SSH: `php artisan storage:link`
* Sin SSH: crea un *Cron Job* temporal (cPanel → **Cron Jobs**) que ejecute una sola vez
  `cd /home/espectro/espectro-crm && php artisan storage:link` y bórralo después.

---

## 8. Optimizar para producción

Con SSH, después de cada instalación o actualización:

```bash
cd ~/espectro-crm
php artisan optimize          # cachea config, rutas, eventos y vistas
```

Sin SSH: crea un Cron Job de una sola ejecución con
`cd /home/espectro/espectro-crm && php artisan optimize` (o simplemente omite este paso: el sistema funciona igual, solo un poco más lento).

> Después de `optimize`, los cambios en `.env` no se aplican hasta limpiar la caché (siguiente sección).

## 9. Limpiar caché

Cuando cambies `.env`, subas código nuevo o algo “no se actualiza”:

* **Con SSH**: `php artisan optimize:clear`
* **Sin SSH**: en File Manager borra los archivos `.php` dentro de `bootstrap/cache/` (**excepto** `.gitignore`) y el contenido de `storage/framework/views/`. Laravel los regenera solo.

---

## 10. Verificación final

1. Abre `https://crm.espectro.mx` → debe aparecer la pantalla de inicio de sesión con estilos.
2. Entra con tu administrador.
3. Crea un cliente de prueba y bórralo.
4. Revisa que en **Configuración** el IVA sea 16 %.

### Problemas comunes

| Síntoma | Causa y solución |
|---------|------------------|
| Error 500 en blanco | Revisa `storage/logs/laravel-AAAA-MM-DD.log`. Suele ser permisos de `storage`, `APP_KEY` vacío o datos de base incorrectos. |
| Página sin estilos | Falta `public/build` (o `public_html/build` en la opción B), o `APP_URL` no coincide con el dominio. |
| "419 Page Expired" al iniciar sesión | `SESSION_SECURE_COOKIE=true` sin HTTPS, o `APP_URL` con `http` en lugar de `https`. |
| Se ve el listado de archivos o el `.env` | La raíz del dominio apunta a `espectro-crm` en lugar de `espectro-crm/public`. Corrígelo de inmediato. |
| `could not find driver` | Activa la extensión `pdo_mysql` en *Select PHP Version*. |
| Error de sintaxis PHP | El dominio usa PHP < 8.3. Cámbialo en *MultiPHP Manager*. |

---

## Despliegue con Git Version Control (recomendado)

En lugar de subir archivos a mano, el servidor descarga el código de GitHub y `deploy.sh` hace el resto (dependencias, migraciones, caché). Requiere Terminal para la configuración inicial.

### Configuración inicial (una sola vez)

1. **Llave de acceso al repositorio privado** (Terminal):

   ```bash
   mkdir -p ~/.ssh && chmod 700 ~/.ssh
   ssh-keygen -t ed25519 -f ~/.ssh/github_crm -N "" -C "cpanel-crm"
   printf 'Host github.com\n  IdentityFile ~/.ssh/github_crm\n  IdentitiesOnly yes\n' >> ~/.ssh/config
   chmod 600 ~/.ssh/config
   ssh-keyscan github.com >> ~/.ssh/known_hosts
   cat ~/.ssh/github_crm.pub
   ```

   Copia la línea que imprime el último comando. En GitHub → repositorio → *Settings → Deploy keys → Add deploy key*, pégala y **no** marques "Allow write access". Prueba con `ssh -T git@github.com`: debe saludar con el nombre del repositorio.

2. **Aparta la instalación manual** (conserva el `.env`):

   ```bash
   mv ~/espectro-crm ~/espectro-crm-manual
   ```

   El sitio queda fuera de línea unos minutos, hasta el paso 5.

3. **Clona desde cPanel** → *Git™ Version Control* → *Create*:
   * Clone a Repository: activado
   * Clone URL: `ssh://git@github.com/altrevino/CRM.git`
   * Repository Path: `espectro-crm`
   * Repository Name: `Espectro CRM`

   La ruta debe ser la misma que ya usa el subdominio (`espectro-crm/public`), así no hay que tocar *Domains*.

4. **Recupera la configuración**:

   ```bash
   cp ~/espectro-crm-manual/.env ~/espectro-crm/.env
   ```

5. **Primer despliegue**:

   ```bash
   cd ~/espectro-crm && bash deploy.sh
   ```

   `deploy.sh` busca solo PHP 8.3 o superior, descarga Composer si hace falta, instala dependencias, aplica migraciones y catálogos, y regenera la caché. Al terminar, el sitio vuelve a estar en línea.

6. Cuando confirmes que todo funciona, borra la copia anterior: `rm -rf ~/espectro-crm-manual`.

### Cada actualización

* **Desde cPanel:** *Git™ Version Control* → *Manage* → *Pull or Deploy* → **Update from Remote** y después **Deploy HEAD Commit** (ejecuta `.cpanel.yml`, que llama a `deploy.sh`). El resultado queda en `~/.cpanel/logs/` (archivos `*_git_deploy.log`).
* **Desde Terminal:** `cd ~/espectro-crm && git pull && bash deploy.sh`

Reglas:

* No edites archivos del código en el servidor: cPanel no despliega si hay cambios sin confirmar en la carpeta (`.env`, `vendor`, `storage` y `composer.phar` están excluidos y no cuentan).
* Antes de una actualización con cambios importantes, respalda la base (ver `RESPALDOS.md`).

## Actualizaciones (sin Git)

1. En tu computadora: obtén la versión nueva, `composer install --no-dev --optimize-autoloader` y, si cambió el diseño, `npm run build`.
2. Sube y reemplaza las carpetas `app`, `bootstrap` (sin borrar `bootstrap/cache`), `config`, `database`, `lang`, `public/build`, `resources`, `routes`, `vendor` y los archivos `composer.json`/`composer.lock`. **No** reemplaces `.env` ni `storage`.
3. Respalda la base (ver `RESPALDOS.md`).
4. Con SSH: `php artisan migrate --force && php artisan optimize`.
   Sin SSH: aplica las migraciones nuevas en una copia local de tu base y vuelve a importarla, o pide al proveedor acceso temporal a Terminal.
5. Limpia caché (sección 9).

Para regenerar `database/install/instalacion.sql` tras agregar migraciones (requiere MySQL/MariaDB local):

```bash
php artisan migrate:fresh --seeder="Database\Seeders\CatalogSeeder"
php artisan crm:create-admin --name="Administrador" --email="admin@espectro.local" --password="CambiarEsta2026"
mysqldump -u root -p --skip-comments --no-tablespaces espectro_crm > database/install/instalacion.sql
```
