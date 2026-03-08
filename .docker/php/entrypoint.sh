#!/bin/sh
set -e

# Démarre le daemon cron
crond -l 2 -L /var/log/cron.log

# Démarre PHP-FPM (commande par défaut de l'image)
exec php-fpm
