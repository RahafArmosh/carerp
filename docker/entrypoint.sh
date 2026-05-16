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

if [ -z "${APP_KEY:-}" ]; then
  echo "ERROR: APP_KEY is not set. Add APP_KEY in Railway variables (run: php artisan key:generate --show)"
  exit 1
fi

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

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

php artisan storage:link --force 2>/dev/null || true

php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

# Railway sets PORT; Apache must listen on it.
PORT="${PORT:-80}"
if [ -f /etc/apache2/ports.conf ]; then
  sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
fi
if [ -f /etc/apache2/sites-available/000-default.conf ]; then
  sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

a2dismod -f mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

exec apache2-foreground
