#!/usr/bin/env bash
set -e

cd /var/www/html

# Map Railway / platform env vars to Laravel names.
if [ -n "${MYSQL_URL:-${DATABASE_URL:-}}" ]; then
  eval "$(php -r "
    \$url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
    \$p = parse_url(\$url);
    if (!\$p) { exit(1); }
    \$user = \$p['user'] ?? '';
    \$pass = \$p['pass'] ?? '';
    \$host = \$p['host'] ?? '127.0.0.1';
    \$port = \$p['port'] ?? '3306';
    \$db = ltrim(\$p['path'] ?? '', '/');
    echo 'export DB_CONNECTION=mysql';
    echo 'export DB_HOST=' . escapeshellarg(\$host);
    echo 'export DB_PORT=' . escapeshellarg((string) \$port);
    echo 'export DB_DATABASE=' . escapeshellarg(\$db);
    echo 'export DB_USERNAME=' . escapeshellarg(\$user);
    echo 'export DB_PASSWORD=' . escapeshellarg(\$pass);
  ")"
fi

export DB_HOST="${DB_HOST:-${MYSQLHOST:-${MYSQL_HOST:-}}}"
export DB_PORT="${DB_PORT:-${MYSQLPORT:-${MYSQL_PORT:-3306}}}"
export DB_DATABASE="${DB_DATABASE:-${MYSQLDATABASE:-${MYSQL_DATABASE:-}}}"
export DB_USERNAME="${DB_USERNAME:-${MYSQLUSER:-${MYSQL_USER:-}}}"
export DB_PASSWORD="${DB_PASSWORD:-${MYSQLPASSWORD:-${MYSQL_PASSWORD:-}}}"

if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
  export APP_URL="${APP_URL:-https://${RAILWAY_PUBLIC_DOMAIN}}"
fi

export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"

# File-based session/cache unless explicitly configured (database sessions need migrations + DB on every request).
export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export CACHE_STORE="${CACHE_STORE:-file}"
export APP_MAINTENANCE_DRIVER="${APP_MAINTENANCE_DRIVER:-file}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export LOG_LEVEL="${LOG_LEVEL:-error}"

# Database sessions hit MySQL on every request; without migrations this causes HTTP 500.
if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ] && [ "${SESSION_DRIVER}" = "database" ] && [ "${ALLOW_DATABASE_SESSION:-}" != "true" ]; then
  export SESSION_DRIVER=file
  echo "Railway: using SESSION_DRIVER=file (set ALLOW_DATABASE_SESSION=true after session table exists)."
fi

if [ -z "${APP_KEY:-}" ]; then
  echo "ERROR: APP_KEY is not set. Add APP_KEY in Railway variables (run: php artisan key:generate --show)"
  exit 1
fi

php -r "
  \$key = getenv('APP_KEY');
  if (str_starts_with(\$key, 'base64:')) {
    \$key = base64_decode(substr(\$key, 7), true);
  }
  if (\$key === false || strlen(\$key) !== 32) {
    fwrite(STDERR, 'ERROR: APP_KEY is invalid (must be base64:... decoding to 32 bytes). Run: php artisan key:generate --show' . PHP_EOL);
    exit(1);
  }
"

# Drop any config cached at image build or from a previous deploy (e.g. invalid temp APP_KEY).
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php bootstrap/cache/events.php bootstrap/cache/services.php 2>/dev/null || true

if [ -n "${DB_HOST}" ]; then
  echo "Waiting for database at ${DB_HOST}:${DB_PORT}..."
  until php -r "
    try {
      new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'),
        getenv('DB_USERNAME') ?: 'root',
        getenv('DB_PASSWORD') ?: ''
      );
      exit(0);
    } catch (Throwable \$e) {
      exit(1);
    }
  " 2>/dev/null; do
    sleep 2
  done
  echo "Database is ready."
else
  echo "WARNING: DB_HOST not set. Link a MySQL service on Railway or set DB_* variables."
fi

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

php artisan storage:link --force 2>/dev/null || true

php artisan package:discover --ansi

php artisan config:clear
php artisan config:cache
php artisan route:cache || php artisan route:clear
php artisan view:cache || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

if [ "${SESSION_DRIVER}" = "database" ]; then
  echo "NOTE: SESSION_DRIVER=database requires a working DB and sessions table (php artisan session:table && migrate)."
fi

# Default 8080 (Railway and local Docker); override with PORT if needed.
PORT="${PORT:-8080}"
if [ -f /etc/apache2/ports.conf ]; then
  sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
fi
if [ -f /etc/apache2/sites-available/000-default.conf ]; then
  sed -i -E "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

a2dismod -f mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

exec apache2-foreground
