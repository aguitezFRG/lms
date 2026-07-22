<?php

return [
    'enabled' => (bool) env('DEMO_MODE', false),
    'profile_session_key' => 'demo_profile_id',
    'database_path' => env('DEMO_DATABASE_PATH', '/persist/database/demo.sqlite'),
    'storage_path' => env('DEMO_STORAGE_PATH', '/persist/storage/app/private'),
    'static_asset_url' => rtrim((string) env('DEMO_STATIC_ASSET_URL', ''), '/'),
    'internal_prefix' => '/__php',
    'max_storage_bytes' => 100 * 1024 * 1024,
    'max_local_pdfs' => 5,
    'max_pdf_bytes' => 10 * 1024 * 1024,
    'archive_version' => 2,
];
