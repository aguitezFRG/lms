import { defineConfig } from 'vite';

export function isExpectedPhpWasmEvalWarning(warning) {
    return warning?.code === 'EVAL'
        && /(?:^|[\\/])node_modules[\\/]php-cgi-wasm[\\/]php8\.4-cgi-worker\.mjs$/.test(warning.id ?? '');
}

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
        rolldownOptions: {
            onwarn(warning, warn) {
                if (isExpectedPhpWasmEvalWarning(warning)) return;

                warn(warning);
            },
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
