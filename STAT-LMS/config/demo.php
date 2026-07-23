<?php

return [
    'enabled' => (bool) env('DEMO_MODE', false),
    'runtime' => env('DEMO_RUNTIME', 'browser'),
    'profile_session_key' => 'demo_profile_id',
    'database_path' => env('DEMO_DATABASE_PATH', '/persist/database/demo.sqlite'),
    'storage_path' => env('DEMO_STORAGE_PATH', '/persist/storage/app/private'),
    'material_disk' => env('DEMO_MATERIAL_DISK', env('FILESYSTEM_DISK', 'local')),
    'static_asset_url' => rtrim((string) env('DEMO_STATIC_ASSET_URL', ''), '/'),
    'internal_prefix' => '/__php',
    'max_storage_bytes' => 100 * 1024 * 1024,
    'max_shared_upload_bytes' => (int) env('DEMO_MAX_SHARED_UPLOAD_BYTES', 250 * 1024 * 1024),
    'shared_upload_warning_bytes' => (int) env('DEMO_SHARED_UPLOAD_WARNING_BYTES', 200 * 1024 * 1024),
    'access_enforced' => (bool) env('CF_ACCESS_ENFORCED', true),
    'access_team_domain' => rtrim((string) env('CF_ACCESS_TEAM_DOMAIN', ''), '/'),
    'access_audience' => (string) env('CF_ACCESS_AUD', ''),
    'reset_hmac_secret' => (string) env('DEMO_RESET_HMAC_SECRET', ''),
    'max_local_pdfs' => 5,
    'max_pdf_bytes' => 10 * 1024 * 1024,
    'archive_version' => 2,
];
