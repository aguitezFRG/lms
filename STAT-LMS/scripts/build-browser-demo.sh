#!/usr/bin/env bash

set -euo pipefail

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
client_root="$project_root/client"
stage_root="$(mktemp -d /tmp/instat-browser-demo.XXXXXX)"
stage_app="$stage_root/app"
demo_database="$stage_root/demo.sqlite"

cleanup() {
    rm -rf -- "$stage_root"
    rm -rf -- \
        "$client_root/public/build" \
        "$client_root/public/css" \
        "$client_root/public/fonts" \
        "$client_root/public/images" \
        "$client_root/public/js" \
        "$client_root/public/pdfs"
    rm -f -- "$client_root/public/laravel-demo-v1.zip"
}
trap cleanup EXIT

cd "$project_root"
npm run build
php artisan config:clear

touch "$demo_database"
DEMO_MODE=true \
DEMO_DATABASE_PATH="$demo_database" \
DEMO_STORAGE_PATH="$stage_root/storage/app/private" \
DB_CONNECTION=sqlite \
DB_DATABASE="$demo_database" \
CACHE_STORE=array \
QUEUE_CONNECTION=sync \
SESSION_DRIVER=cookie \
php artisan migrate:fresh --seed --seeder=Database\\Seeders\\DemoDatabaseSeeder --force --no-interaction

mkdir -p "$stage_app"
cp -a app artisan bootstrap composer.json composer.lock config database package.json public resources routes "$stage_app/"
mkdir -p "$stage_app/storage/framework/cache" "$stage_app/storage/framework/sessions" "$stage_app/storage/framework/views" "$stage_app/storage/logs" "$stage_app/seed"
cp "$demo_database" "$stage_app/seed/demo.sqlite"

rm -rf -- "$stage_app/database/seeders/.digital_copies" "$stage_app/public/build/assets" "$stage_app/public/hot"

COMPOSER_CACHE_DIR="${COMPOSER_CACHE_DIR:-/tmp/instat-composer-cache}" \
composer install --working-dir="$stage_app" --no-dev --no-interaction --prefer-dist --optimize-autoloader

# PHP-WASM pays Laravel's bootstrap cost on every CGI request. Precompute the
# deployment-stable discovery caches so page and Livewire requests do not scan
# routes, events, Filament components, and Blade icon sets repeatedly.
(
    cd "$stage_app"
    export APP_ENV=production
    export APP_KEY='base64:vlA79YwQ2RrJgO7n8jvFzRY5+Ou1I8Pc8GUzF2mYflE='
    export APP_URL='http://localhost/__php'
    export CACHE_STORE=file
    export DB_CONNECTION=sqlite
    export DB_DATABASE="$demo_database"
    export DEMO_DATABASE_PATH="$demo_database"
    export DEMO_MODE=true
    export DEMO_STORAGE_PATH="$stage_root/storage/app/private"
    export LIVEWIRE_RELEASE_TOKEN=instat-demo-runtime-v1
    export QUEUE_CONNECTION=sync
    export SESSION_DRIVER=cookie

    php artisan event:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan filament:cache-components --no-interaction
    php artisan icons:cache --no-interaction

    # Cache configuration with deploy-time placeholders. The worker replaces
    # the public origin after unpacking, while the staging path is rewritten to
    # the stable in-browser application root below.
    export APP_URL='https://demo.invalid/__php'
    export ASSET_URL='https://demo.invalid/__php'
    export DB_DATABASE='/persist/database/demo.sqlite'
    export DEMO_DATABASE_PATH='/persist/database/demo.sqlite'
    export DEMO_STATIC_ASSET_URL='https://demo.invalid'
    export DEMO_STORAGE_PATH='/persist/storage/app/private'
    php artisan config:cache --no-interaction
    sed -i "s|$stage_app|/preload/app|g" bootstrap/cache/config.php
)

# Runtime requests use the already-emitted public assets on the static host. Keep
# only Laravel's front controller and Vite manifest inside the PHP payload.
rm -rf -- "$stage_app/public/css" "$stage_app/public/fonts" "$stage_app/public/images" "$stage_app/public/js" "$stage_app/public/build/assets"

# Composer distributions include development material and source maps which are
# not executable runtime dependencies. Removing them keeps the single payload
# below static-host upload limits without changing application PHP or views.
find "$stage_app/vendor" -type d \( -name .github -o -name docs -o -name doc -o -name test -o -name tests -o -name Tests \) -prune -exec rm -rf -- {} +
find "$stage_app/vendor" -type f \( -name '*.map' -o -name '*.md' \) -delete

# The watermark service explicitly uses TCPDF's Helvetica core font. The large
# Unicode font collection is unnecessary for importing an existing PDF page.
find "$stage_app/vendor/tecnickcom/tcpdf/fonts" -maxdepth 1 -type f \
    ! -name 'helvetica*' \
    ! -name 'courier*' \
    ! -name 'times*' \
    ! -name 'symbol*' \
    ! -name 'zapfdingbats*' \
    -delete

# The demo locale is English; Filament's other translations account for many
# thousands of payload files and are not reachable in this deployment.
find "$stage_app/vendor/filament" -path '*/resources/lang/*' -mindepth 4 -maxdepth 4 -type d ! -name en -prune -exec rm -rf -- {} +

find "$stage_app" -name '.env' -o -name '.env.*' | while read -r forbidden; do
    echo "Refusing to package secret-bearing file: $forbidden" >&2
    exit 1
done

rm -rf -- "$client_root/public/build" "$client_root/public/css" "$client_root/public/fonts" "$client_root/public/images" "$client_root/public/js" "$client_root/public/pdfs"
mkdir -p "$client_root/public/build" "$client_root/public/css" "$client_root/public/fonts" "$client_root/public/images" "$client_root/public/js" "$client_root/public/pdfs"
cp -a public/build/. "$client_root/public/build/"
for static_dir in css fonts images js; do
    if [[ -d "public/$static_dir" ]]; then
        cp -a "public/$static_dir/." "$client_root/public/$static_dir/"
    fi
done
cp -a database/seeders/.digital_copies/. "$client_root/public/pdfs/"

# Vercel compresses responses at the edge. Precompressed Vite copies would be
# uploaded as separate files, and these editor component bundles are not used by
# any schema in this application (the material form uses FileUpload).
find "$client_root/public/build" -type f \( -name '*.gz' -o -name '*.br' \) -delete
rm -f -- \
    "$client_root/public/js/filament/forms/components/code-editor.js" \
    "$client_root/public/js/filament/forms/components/markdown-editor.js" \
    "$client_root/public/js/filament/forms/components/rich-editor.js"

payload_name="laravel-demo-v1.zip"
rm -f -- "$client_root/public/$payload_name"
(
    cd "$stage_app"
    zip -q -r -9 "$client_root/public/$payload_name" .
)

cd "$client_root"
npm run build

payload_bytes="$(stat -c '%s' "dist/$payload_name")"
payload_sha="$(sha256sum "dist/$payload_name" | cut -d ' ' -f 1)"

php -r '
$manifest = [
    "runtimeVersion" => 1,
    "schemaVersion" => 1,
    "phpVersion" => "8.4",
    "payloads" => [[
        "filename" => $argv[1],
        "bytes" => (int) $argv[2],
        "sha256" => $argv[3],
    ]],
];
file_put_contents($argv[4], json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
' "$payload_name" "$payload_bytes" "$payload_sha" "dist/demo-runtime-manifest.json"

if rg -l --hidden --glob '!demo-runtime-manifest.json' --glob '!*.map' 'APP_KEY=|DB_PASSWORD=|GOOGLE_CLIENT_SECRET=' dist; then
    echo 'Secret-like environment data entered the static artifact.' >&2
    exit 1
fi

rm -rf -- "$project_root/.vercel/output/static"
mkdir -p "$project_root/.vercel/output/static"
cp -a dist/. "$project_root/.vercel/output/static/"
cp "$project_root/scripts/vercel-output-config.json" "$project_root/.vercel/output/config.json"

artifact_bytes="$(du -sb dist | cut -f 1)"
artifact_files="$(find dist -type f | wc -l)"
printf 'Browser demo built: %s bytes across %s files\n' "$artifact_bytes" "$artifact_files"

if (( artifact_bytes > 100000000 )); then
    echo 'Static artifact exceeds the 100 MB deployment budget.' >&2
    exit 1
fi

if (( artifact_files > 15000 )); then
    echo 'Static artifact exceeds the 15,000 file deployment budget.' >&2
    exit 1
fi
