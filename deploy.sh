#!/usr/bin/env bash
# Despliegue en el servidor (cPanel): dependencias, migraciones y caché.
# Uso:  bash deploy.sh        (o el botón "Deploy HEAD Commit" de cPanel, vía .cpanel.yml)
# Variables opcionales: PHP_BIN=/ruta/a/php  para forzar una versión concreta.
set -euo pipefail
cd "$(dirname "$0")"

# 1. PHP 8.3 o superior (la terminal de cPanel puede traer otra versión por defecto).
if [ -z "${PHP_BIN:-}" ]; then
    for candidate in \
        /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/ea-php83/root/usr/bin/php \
        /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php \
        "$(command -v php || true)"; do
        if [ -n "$candidate" ] && [ -x "$candidate" ] && "$candidate" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' 2>/dev/null; then
            PHP_BIN="$candidate"
            break
        fi
    done
fi
if [ -z "${PHP_BIN:-}" ]; then
    echo "ERROR: no se encontró PHP 8.3 o superior. Define PHP_BIN=/ruta/a/php." >&2
    exit 1
fi
echo "PHP: $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"

if [ ! -f .env ]; then
    echo "ERROR: falta el archivo .env en $(pwd). Cópialo antes de desplegar." >&2
    exit 1
fi

# 2. Composer: el del sistema si es un script PHP; si no, descarga composer.phar local.
COMPOSER_BIN=""
for candidate in "$(command -v composer || true)" /opt/cpanel/composer/bin/composer ./composer.phar; do
    if [ -n "$candidate" ] && [ -f "$candidate" ] && head -c 200 "$candidate" | grep -qi "php"; then
        COMPOSER_BIN="$candidate"
        break
    fi
done
if [ -z "$COMPOSER_BIN" ]; then
    echo "Descargando composer.phar…"
    "$PHP_BIN" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    "$PHP_BIN" composer-setup.php --quiet
    rm -f composer-setup.php
    COMPOSER_BIN=./composer.phar
fi

# 3. Modo mantenimiento mientras se actualiza (se levanta aunque algo falle).
"$PHP_BIN" artisan down --retry=15 || true
trap '"$PHP_BIN" artisan up || true' EXIT

export COMPOSER_MEMORY_LIMIT=-1
"$PHP_BIN" "$COMPOSER_BIN" install --no-dev --optimize-autoloader --no-interaction --no-progress

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan db:seed --class=CatalogSeeder --force
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

chmod -R u+rwX,go+rX storage bootstrap/cache

echo "Despliegue terminado: $(git log -1 --format='%h %s' 2>/dev/null || echo 'sin git')"
