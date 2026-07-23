#!/usr/bin/env bash

set -euo pipefail

required_variables=(
    APP_KEY
    APP_URL
    CF_ACCESS_AUD
    CF_ACCESS_TEAM_DOMAIN
    DB_URL
    DEMO_RESET_HMAC_SECRET
    SUPABASE_S3_ACCESS_KEY_ID
    SUPABASE_S3_BUCKET
    SUPABASE_S3_ENDPOINT
    SUPABASE_S3_REGION
    SUPABASE_S3_SECRET_ACCESS_KEY
)

for variable_name in "${required_variables[@]}"; do
    if [[ -z "${!variable_name:-}" ]]; then
        echo "Required environment variable is missing: ${variable_name}" >&2
        exit 1
    fi
done

export PORT="${PORT:-10000}"
envsubst '${PORT}' \
    < /etc/nginx/templates/default.conf.template \
    > /etc/nginx/conf.d/default.conf

cd /var/www/html

php artisan config:clear --no-interaction
php artisan migrate --force --no-interaction
php artisan demo:bootstrap-shared --force --no-interaction
php artisan optimize --no-interaction

php-fpm --daemonize
exec nginx -g 'daemon off;'
