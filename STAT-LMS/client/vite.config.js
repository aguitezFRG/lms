import { defineConfig } from 'vite';

export default defineConfig({
    base: '/',
    preview: {
        headers: {
            'Service-Worker-Allowed': '/',
        },
    },
    build: {
        outDir: 'dist',
        sourcemap: false,
        target: 'es2022',
        rollupOptions: {
            output: {
                assetFileNames: 'runtime/[name]-[hash][extname]',
                chunkFileNames: 'runtime/[name]-[hash].js',
                entryFileNames: 'runtime/[name]-[hash].js',
            },
        },
    },
    worker: {
        format: 'es',
    },
});
