# Respaldos y restauración

El CRM guarda **toda** la información en la base MySQL. No almacena documentos. Por eso un respaldo completo son dos cosas:

| Qué | Dónde | Frecuencia sugerida |
|-----|-------|---------------------|
| **Base de datos** | MySQL (`espectro_espectro_crm` en cPanel) | Semanal, y siempre antes de actualizar |
| **Archivo `.env`** | `/home/espectro/espectro-crm/.env` | Cada vez que lo cambies (contiene `APP_KEY` y la contraseña de la base) |

El resto del código se puede volver a descargar del repositorio. Opcionalmente respalda `storage/logs` si quieres conservar los registros de errores.

> Guarda el `.env` en un lugar privado (por ejemplo una carpeta personal cifrada de Drive), nunca en un lugar compartido: contiene credenciales.

---

## Exportar la base de datos

### Desde cPanel (sin SSH)

**Opción 1: phpMyAdmin**

1. cPanel → **phpMyAdmin** → selecciona la base.
2. Pestaña **Exportar** → método *Rápido*, formato *SQL* → **Continuar**.
3. Se descarga un archivo `.sql`. Renómbralo con fecha, p. ej. `espectro-crm-2026-10-01.sql`.

**Opción 2: Backup de cPanel**

cPanel → **Backup** → *Partial Backups* → *Download a MySQL Database Backup* → clic en el nombre de la base. Descarga un `.sql.gz`.

### Con SSH

```bash
mysqldump -u espectro_crmuser -p --single-transaction --no-tablespaces espectro_espectro_crm \
  | gzip > ~/respaldos/espectro-crm-$(date +%F).sql.gz
```

### Respaldo automático semanal (opcional)

cPanel → **Cron Jobs** → *Once Per Week* con el comando (todo en una línea):

```bash
mkdir -p ~/respaldos && mysqldump -u espectro_crmuser -p'CONTRASEÑA' --single-transaction --no-tablespaces espectro_espectro_crm | gzip > ~/respaldos/espectro-crm-$(date +\%F).sql.gz && find ~/respaldos -name "*.sql.gz" -mtime +60 -delete
```

Conserva 60 días. Descarga una copia a tu computadora o a Drive al menos una vez al mes: un respaldo que solo vive en el mismo servidor no protege contra la pérdida del hosting.

---

## Restaurar

### Restaurar solo la base (mismo servidor)

1. **Respalda primero la base actual** aunque esté dañada.
2. phpMyAdmin → selecciona la base → pestaña **Operaciones** → *Vaciar la base de datos* (o elimina todas las tablas desde *Estructura* → *Seleccionar todo* → *Eliminar*).
3. Pestaña **Importar** → elige el `.sql` (o `.sql.gz`) → **Continuar**.
4. Limpia la caché (borra los `.php` de `bootstrap/cache/` o `php artisan optimize:clear`).

Con SSH:

```bash
gunzip < espectro-crm-2026-10-01.sql.gz | mysql -u espectro_crmuser -p espectro_espectro_crm
php artisan optimize:clear
```

### Restaurar todo en un servidor nuevo

1. Instala el código siguiendo `INSTALACION_CPANEL.md` pasos 1 a 3.
2. Sube tu `.env` respaldado y ajusta `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` y `APP_URL` a los del nuevo servidor. **Conserva el mismo `APP_KEY`.**
3. Importa el `.sql` en la nueva base (en lugar de `instalacion.sql`).
4. Con SSH ejecuta `php artisan migrate --force` por si el código es más nuevo que el respaldo.
5. Permisos (paso 6) y limpieza de caché (paso 9).

### Restaurar en tu computadora (para revisar datos)

```bash
mysql -u root -p -e "CREATE DATABASE espectro_restaurada CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p espectro_restaurada < espectro-crm-2026-10-01.sql
```

Apunta `DB_DATABASE=espectro_restaurada` en tu `.env` local y ejecuta `php artisan serve`.

---

## Notas

* Los registros eliminados desde el CRM (clientes, ranchos, servicios, cotizaciones y pagos) no se borran físicamente: quedan con `deleted_at` y en el historial. Un respaldo los incluye.
* Si pierdes el `APP_KEY`, la información del CRM no se pierde (no se cifran datos con esa llave), pero se invalidan las sesiones abiertas. Genera una nueva con `php artisan key:generate`.
