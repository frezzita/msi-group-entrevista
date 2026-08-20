#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"

php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
