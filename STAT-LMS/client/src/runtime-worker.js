/* eslint-disable no-restricted-globals */
import { PhpCgiWebBase } from 'php-cgi-wasm/PhpCgiWebBase';
import Php84CgiWorker from 'php-cgi-wasm/php8.4-cgi-worker.mjs';
import dom from 'php-wasm-dom/8.4.mjs';
import gd from 'php-wasm-gd/8.4.mjs';
import intl from 'php-wasm-intl/8.4.mjs';
import libxml from 'php-wasm-libxml';
import libzip from 'php-wasm-libzip/8.4.mjs';
import mbstring from 'php-wasm-mbstring/8.4.mjs';
import openssl from 'php-wasm-openssl/8.4.mjs';
import sqlite from 'php-wasm-sqlite/8.4.mjs';
import xml from 'php-wasm-xml/8.4.mjs';
import zlib from 'php-wasm-zlib/8.4.mjs';
import { unzipSync } from 'fflate';

const APP_ROOT = '/preload/app';
const DATABASE_PATH = '/persist/database/demo.sqlite';
const STORAGE_PATH = '/persist/storage/app/private';

class Php84Worker extends PhpCgiWebBase {
    constructor(options = {}) {
        super(Promise.resolve({ default: Php84CgiWorker }), { ...options, version: '8.4' });
    }
}

function mkdirTree(FS, path) {
    let current = '';
    for (const segment of path.split('/').filter(Boolean)) {
        current += `/${segment}`;
        if (! FS.analyzePath(current).exists) FS.mkdir(current);
    }
}

function writeTree(FS, entries, root) {
    for (const [name, bytes] of Object.entries(entries)) {
        const destination = `${root}/${name}`.replace(/\/+/, '/');
        if (name.endsWith('/')) {
            mkdirTree(FS, destination);
            continue;
        }
        mkdirTree(FS, destination.slice(0, destination.lastIndexOf('/')));
        FS.writeFile(destination, bytes);
    }
}

function syncFilesystem(FS, populate) {
    return new Promise((resolve, reject) => FS.syncfs(populate, (error) => error ? reject(error) : resolve()));
}

async function bootstrapDemo(runtime, payload, manifest) {
    try {
        return await navigator.locks.request('instat-demo-bootstrap', async () => {
            const php = await runtime.binary;
            const entries = unzipSync(new Uint8Array(payload));
            if (! entries['artisan'] || ! entries['seed/demo.sqlite']) throw new Error('The Laravel payload is incomplete.');

            const cachedConfigPath = 'bootstrap/cache/config.php';
            if (entries[cachedConfigPath]) {
                const decoder = new TextDecoder();
                const encoder = new TextEncoder();
                const cachedConfig = decoder
                    .decode(entries[cachedConfigPath])
                    .replaceAll('https://demo.invalid', self.location.origin);
                entries[cachedConfigPath] = encoder.encode(cachedConfig);
            }

            writeTree(php.FS, entries, APP_ROOT);
            mkdirTree(php.FS, '/persist/database');
            mkdirTree(php.FS, STORAGE_PATH);
            mkdirTree(php.FS, `${APP_ROOT}/storage/framework/cache`);
            mkdirTree(php.FS, `${APP_ROOT}/storage/framework/cache/data`);
            mkdirTree(php.FS, `${APP_ROOT}/storage/framework/sessions`);
            mkdirTree(php.FS, `${APP_ROOT}/storage/framework/views`);
            mkdirTree(php.FS, `${APP_ROOT}/storage/logs`);

            if (! php.FS.analyzePath(DATABASE_PATH).exists) {
                php.FS.writeFile(DATABASE_PATH, entries['seed/demo.sqlite']);
            }

            php.FS.writeFile('/config/runtime-schema', String(manifest.schemaVersion));
            await syncFilesystem(php.FS, false);
            return { ready: true, schemaVersion: manifest.schemaVersion };
        });
    } catch (error) {
        throw {
            message: error instanceof Error ? error.message : String(error),
            stack: error instanceof Error ? error.stack : '',
        };
    }
}

const staticPrefixes = ['/__php/build/', '/__php/css/', '/__php/fonts/', '/__php/images/', '/__php/js/'];
const sharedLibs = [libxml, zlib, libzip, gd, intl, openssl, mbstring, sqlite, xml, dom];

async function addCsrfBodyField(request) {
    const token = request.headers.get('x-csrf-token');
    if (! token || ! ['POST', 'PUT', 'PATCH'].includes(request.method)) return request;

    const contentType = request.headers.get('content-type') || '';
    const headers = new Headers(request.headers);
    headers.delete('content-length');

    if (contentType.includes('application/json')) {
        const body = await request.clone().json();
        body._token = token;
        return new Request(request, { headers, body: JSON.stringify(body) });
    }

    if (contentType.includes('multipart/form-data')) {
        const body = await request.clone().formData();
        body.set('_token', token);
        headers.delete('content-type');
        return new Request(request, { headers, body });
    }

    return request;
}

const runtime = new Php84Worker({
    prefix: '/__php',
    docroot: `${APP_ROOT}/public`,
    entrypoint: 'index.php',
    sharedLibs,
    staticCacheTime: 0,
    dynamicCacheTime: 0,
    actions: { bootstrapDemo },
    env: {
        APP_ENV: 'production',
        APP_DEBUG: 'false',
        APP_KEY: 'base64:vlA79YwQ2RrJgO7n8jvFzRY5+Ou1I8Pc8GUzF2mYflE=',
        APP_URL: `${self.location.origin}/__php`,
        ASSET_URL: `${self.location.origin}/__php`,
        DEMO_MODE: 'true',
        DEMO_DATABASE_PATH: DATABASE_PATH,
        DEMO_STORAGE_PATH: STORAGE_PATH,
        DEMO_STATIC_ASSET_URL: self.location.origin,
        LOG_CHANNEL: 'single',
        LIVEWIRE_RELEASE_TOKEN: 'instat-demo-runtime-v1',
        CACHE_STORE: 'file',
        QUEUE_CONNECTION: 'sync',
        SESSION_DRIVER: 'cookie',
        MAIL_MAILER: 'log',
    },
    ini: `
        date.timezone=Asia/Manila
        expose_php=0
        memory_limit=512M
        upload_max_filesize=10M
        post_max_size=12M
    `,
    types: {
        css: 'text/css; charset=utf-8',
        js: 'text/javascript; charset=utf-8',
        json: 'application/json',
        png: 'image/png',
        svg: 'image/svg+xml',
        woff2: 'font/woff2',
    },
    vHosts: [{ pathPrefix: '/__php', directory: `${APP_ROOT}/public`, entrypoint: 'index.php' }],
});

self.addEventListener('install', (event) => runtime.handleInstallEvent(event));
self.addEventListener('activate', (event) => runtime.handleActivateEvent(event));
self.addEventListener('message', (event) => runtime.handleMessageEvent(event));
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    const staticPrefix = staticPrefixes.find((prefix) => url.pathname.startsWith(prefix));
    if (staticPrefix) {
        const staticUrl = new URL(url.pathname.slice('/__php'.length) + url.search, self.location.origin);
        event.respondWith(fetch(new Request(staticUrl, event.request)));
        return;
    }
    if (/^\/__php\/livewire-[^/]+\/(update|upload-file)$/.test(url.pathname)) {
        event.respondWith(addCsrfBodyField(event.request).then((request) => runtime.request(request)));
        return;
    }
    runtime.handleFetchEvent(event);
});
