#!/bin/sh
set -e

# Run composer install if vendor is missing
if [ ! -f "/var/www/html/vendor/autoload.php" ]; then
  echo "⚡ vendor not found, running composer install..."
  composer install --ignore-platform-reqs --no-interaction --prefer-dist
fi

if [ $# -gt 0 ]; then
    exec gosu $WWWUSER "$@"
else
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi
