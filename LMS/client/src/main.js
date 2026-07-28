import runtimeWorkerUrl from './runtime-worker.js?worker&url';
import { internalPath, MANIFEST_PATH, verifyPayload } from './runtime-contract.js';
import { applyTheme, readStoredTheme, STORAGE_KEY } from './theme.js';
import './styles.css';

const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

function syncShellTheme() {
    return applyTheme(readStoredTheme(localStorage), document.documentElement, systemThemeQuery.matches);
}

syncShellTheme();
systemThemeQuery.addEventListener('change', () => {
    if (readStoredTheme(localStorage) === 'system') syncShellTheme();
});
addEventListener('storage', (event) => {
    if (event.key === STORAGE_KEY || event.key === 'theme') syncShellTheme();
});

const startup = document.querySelector('#startup');
const message = document.querySelector('#startup-message');
const progress = document.querySelector('#startup-progress');
const fatal = document.querySelector('#fatal');
const fatalMessage = document.querySelector('#fatal-message');
const frame = document.querySelector('#laravel');

function status(text, value) {
    message.textContent = text;
    progress.value = value;
}

function send(worker, action, params = [], transfer = []) {
    return new Promise((resolve, reject) => {
        const token = crypto.randomUUID();
        const listener = (event) => {
            if (event.data?.re !== token) return;
            navigator.serviceWorker.removeEventListener('message', listener);
            event.data.error ? reject(new Error([event.data.error.message || 'Browser runtime action failed.', event.data.error.stack].filter(Boolean).join('\n'))) : resolve(event.data.result);
        };
        navigator.serviceWorker.addEventListener('message', listener);
        worker.postMessage({ action, token, params }, transfer);
    });
}

function waitForWorkerState(worker, expected) {
    if (worker.state === expected) return Promise.resolve();

    return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(new Error(`The PHP service worker did not reach ${expected}.`)), 30_000);
        worker.addEventListener('statechange', () => {
            if (worker.state !== expected) return;
            clearTimeout(timeout);
            resolve();
        });
    });
}

function waitForController(scriptUrl) {
    if (navigator.serviceWorker.controller?.scriptURL === scriptUrl) return Promise.resolve();

    return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(new Error('The new PHP service worker did not take control.')), 30_000);
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (navigator.serviceWorker.controller?.scriptURL !== scriptUrl) return;
            clearTimeout(timeout);
            resolve();
        });
    });
}

async function start() {
    fatal.hidden = true;
    status('Checking the deployment manifest…', 10);

    const manifestResponse = await fetch(MANIFEST_PATH, { cache: 'no-store' });
    if (! manifestResponse.ok) throw new Error(`Runtime manifest request failed (${manifestResponse.status}).`);
    const manifest = await manifestResponse.json();

    status('Verifying the native Laravel payload…', 25);
    const payload = await verifyPayload(manifest);

    status('Starting PHP 8.4 and browser-local SQLite…', 50);
    const expectedWorkerUrl = new URL(runtimeWorkerUrl, location.href).href;
    const registration = await navigator.serviceWorker.register(runtimeWorkerUrl, { type: 'module', scope: '/' });
    await registration.update();
    const pendingWorker = registration.installing || registration.waiting;
    if (pendingWorker) await waitForWorkerState(pendingWorker, 'activated');
    await navigator.serviceWorker.ready;
    const worker = registration.active;
    if (! worker || worker.scriptURL !== expectedWorkerUrl) throw new Error('The expected PHP service worker did not activate.');
    await waitForController(expectedWorkerUrl);

    status('Mounting Laravel, Filament, and the demo database…', 70);
    await send(worker, 'bootstrapDemo', [payload, manifest], [payload]);

    status('Opening the real Filament application…', 95);
    frame.src = internalPath(location.pathname) + location.search + location.hash;
    frame.hidden = false;
    startup.hidden = true;
}

frame.addEventListener('load', () => {
    try {
        const url = new URL(frame.contentWindow.location.href);
        if (url.origin !== location.origin || ! url.pathname.startsWith('/__php')) return;
        const outerPath = url.pathname.slice('/__php'.length) || '/';
        history.replaceState(null, '', `${outerPath}${url.search}${url.hash}`);
    } catch {
        // The iframe is same-origin in supported deployments; startup still works if inspection is unavailable.
    }
});

addEventListener('popstate', () => {
    frame.src = internalPath(location.pathname) + location.search + location.hash;
});

document.querySelector('#retry').addEventListener('click', () => location.reload());

start().catch((error) => {
    console.error(error);
    status('The browser runtime could not start.', 100);
    progress.removeAttribute('value');
    fatal.hidden = false;
    fatalMessage.textContent = error instanceof Error ? `${error.message}\n\n${error.stack || ''}` : String(error);
});
