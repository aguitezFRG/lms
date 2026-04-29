import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import viteCompression from 'vite-plugin-compression';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/user/theme.css',
                'resources/js/pdf-viewer.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
        viteCompression({ algorithm: 'gzip', ext: '.gz', threshold: 10240 }),
        viteCompression({ algorithm: 'brotliCompress', ext: '.br', threshold: 10240 }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
