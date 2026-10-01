# Espectro CRM

CRM web de **Espectro Soluciones** para administrar prospectos, clientes, ranchos, servicios de censo de fauna, cotizaciones, pagos, seguimientos y censos programados.

* Laravel 13 · Livewire 4 · Tailwind CSS 4 · Alpine.js · MySQL/MariaDB
* Funciona en hosting compartido con cPanel (solo PHP + MySQL; no requiere Node.js en el servidor).
* Todo en español, fechas `DD/MM/AAAA`, moneda MXN, zona horaria `America/Monterrey`.

Documentación adicional:

| Documento | Contenido |
|-----------|-----------|
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Arquitectura, entidades, relaciones, reglas de negocio, navegación y decisiones técnicas |
| [`docs/METRICAS.md`](docs/METRICAS.md) | Fórmula exacta de cada KPI, indicador y alerta |
| [`docs/INSTALACION_CPANEL.md`](docs/INSTALACION_CPANEL.md) | Despliegue en hosting cPanel, con y sin SSH |
| [`docs/RESPALDOS.md`](docs/RESPALDOS.md) | Qué respaldar, cómo exportar la base y cómo restaurar |

---

## Instalación local

### 1. Requisitos

| Software | Versión | Para qué |
|----------|---------|----------|
| PHP | 8.3 o superior | Ejecutar la aplicación |
| Extensiones PHP | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `bcmath`, `intl` | Requeridas por Laravel |
| Composer | 2.x | Dependencias PHP |
| MySQL o MariaDB | MySQL 8.0+ / MariaDB 10.4+ | Base de datos |
| Node.js y npm | Node 20+ | **Solo** si modificas estilos o JavaScript (los archivos compilados ya vienen en `public/build`) |

Formas sencillas de tenerlo todo:

* **macOS**: [Laravel Herd](https://herd.laravel.com) (PHP + Composer) y [DBngin](https://dbngin.com) (MySQL).
* **Windows**: [Laragon](https://laragon.org) (PHP, Composer y MySQL en un solo instalador).
* **Linux**: `sudo apt install php8.3 php8.3-{mysql,mbstring,xml,bcmath,intl,curl,zip} composer mariadb-server`.

Comprueba:

```bash
php -v
composer -V
mysql --version
```

### 2. Clonar o copiar el proyecto

```bash
git clone https://github.com/altrevino/CRM.git espectro-crm
cd espectro-crm
```

(Si recibiste un .zip, descomprímelo y entra a la carpeta.)

### 3. Crear el archivo `.env`

```bash
cp .env.example .env
```

En Windows (PowerShell): `copy .env.example .env`

### 4. Crear la base de datos

```bash
mysql -u root -p -e "CREATE DATABASE espectro_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

También puedes crearla desde phpMyAdmin, TablePlus o HeidiSQL con el nombre `espectro_crm` y cotejamiento `utf8mb4_unicode_ci`.

### 5. Configurar la conexión MySQL

Edita `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=espectro_crm
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

### 6. Instalar dependencias y generar la llave

```bash
composer install
php artisan key:generate
```

### 7. Ejecutar migraciones

```bash
php artisan migrate
```

### 8. Ejecutar seeders

Para **evaluar** el sistema con datos de demostración (5 clientes, 8 ranchos, 12 servicios en todas las etapas, cotizaciones con versiones, pagos parciales, tareas vencidas/hoy/futuras y censos programados):

```bash
php artisan db:seed
```

Usuarios de demostración (contraseña `password`):

| Correo | Rol |
|--------|-----|
| `admin@espectro.local` | Administrador |
| `operaciones@espectro.local` | Usuario |

Para una instalación **limpia** (sin datos de prueba), carga solo los catálogos (estados, especies, formas de pago, configuración):

```bash
php artisan db:seed --class=CatalogSeeder
```

Para empezar de cero en cualquier momento (borra todo):

```bash
php artisan migrate:fresh --seed
```

### 9. Compilar assets (opcional)

Los archivos compilados ya están en `public/build`. Solo si cambias CSS, JS o clases Tailwind en las vistas:

```bash
npm install
npm run build
```

Durante el desarrollo puedes usar `npm run dev` para recarga automática.

### 10. Crear tu usuario administrador

```bash
php artisan crm:create-admin
```

Pregunta nombre, correo y contraseña (mínimo 8 caracteres con letras y números). También acepta opciones:

```bash
php artisan crm:create-admin --name="Alonso Treviño" --email="alonso@espectro.mx" --password="UnaClaveSegura2026"
```

Si el correo ya existe, actualiza ese usuario y lo deja como administrador activo.

### 11. Ejecutar localmente

```bash
php artisan serve
```

Abre <http://localhost:8000> e inicia sesión. Con Herd o Laragon también puedes usar el dominio local que asignan (por ejemplo `http://espectro-crm.test`).

---

## Pruebas automatizadas

```bash
php artisan test
```

Usan SQLite en memoria (no tocan tu base). Cubren: relaciones cliente → rancho → servicio → cotización, una sola cotización aceptada (servicio e índice único), cálculo con y sin IVA, IVA histórico, versiones, saldo y pagos parciales, pagos negativos, cambio de etapa y su historial, Kanban con confirmación de Perdido, censos programados (con y sin fecha), permisos por rol, usuario desactivado, límite de intentos de login, comentarios inmutables, cotizaciones sin seguimiento y carga de todas las pantallas.

Para correrlas contra MySQL, crea una base vacía `espectro_test` y ejecuta:

```bash
DB_CONNECTION=mysql DB_DATABASE=espectro_test DB_USERNAME=root DB_PASSWORD=... php vendor/bin/phpunit
```

## Uso rápido

* **Nuevo servicio** (botón superior): busca o crea cliente → elige o crea rancho → captura el servicio y, opcionalmente, el primer seguimiento. Todo en una pantalla.
* **Ficha del servicio**: cambiar etapa, cotizaciones (versiones, enviar, aceptar), pagos con barra de avance, tareas, comentarios y línea de tiempo.
* **Pipeline**: Kanban; arrastra las tarjetas entre columnas. Mover a *Perdido* pide confirmación.
* **Censos programados**: calendario mensual y lista; un servicio aparece aquí al estar *Confirmado* con fecha de censo.
* **Exportar**: Clientes, Ranchos, Cotizaciones, Pagos y Censos programados exportan CSV (se abre directo en Excel) respetando los filtros activos.
* **Buscador global** (tecla `/`): clientes, teléfonos, ranchos, municipios y cotizaciones.

## Estructura del código

```
app/Enums          Etapas, estatus, tipos y roles (colores y etiquetas en un solo lugar)
app/Livewire       Pantallas (Dashboard, Clients, Ranches, Opportunities, Quotes, Payments, Census, Tasks, Settings)
app/Livewire/Forms Formularios modales reutilizables
app/Models         Modelos Eloquent con scopes de consulta
app/Observers      Historial automático
app/Policies       Permisos por entidad
app/Services       Reglas de negocio (cotizaciones, pagos, etapas, métricas, alertas, exportación, duplicados)
resources/views    Vistas Blade (livewire/, components/, layouts/)
database/          Migraciones, seeders, factories y SQL de instalación para cPanel
tests/Feature      Pruebas de reglas críticas
```

## Comandos útiles

| Comando | Uso |
|---------|-----|
| `php artisan crm:create-admin` | Crear o restablecer un administrador |
| `php artisan db:seed --class=CatalogSeeder` | Cargar/actualizar catálogos sin duplicar |
| `php artisan optimize` | Cachear configuración, rutas y vistas (producción) |
| `php artisan optimize:clear` | Limpiar todas las cachés |
| `php artisan test` | Ejecutar pruebas |
