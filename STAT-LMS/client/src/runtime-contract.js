export const PHP_PREFIX = '/__php';
export const MANIFEST_PATH = '/demo-runtime-manifest.json';

export function validateManifest(manifest) {
    if (! manifest || typeof manifest !== 'object') throw new Error('The runtime manifest is missing.');
    if (manifest.runtimeVersion !== 1) throw new Error('Unsupported browser runtime version.');
    if (manifest.phpVersion !== '8.4') throw new Error('This deployment does not contain the required PHP 8.4 runtime.');
    if (! Number.isInteger(manifest.schemaVersion) || manifest.schemaVersion < 1) throw new Error('Invalid demo schema version.');
    if (! Array.isArray(manifest.payloads) || manifest.payloads.length !== 1) throw new Error('The Laravel payload declaration is incomplete.');

    for (const asset of manifest.payloads) {
        if (! asset.filename || ! Number.isInteger(asset.bytes) || asset.bytes < 1 || ! /^[a-f0-9]{64}$/.test(asset.sha256)) {
            throw new Error('The Laravel payload metadata is invalid.');
        }
    }

    return manifest;
}

export async function sha256Hex(data) {
    const bytes = data instanceof ArrayBuffer ? data : data.buffer.slice(data.byteOffset, data.byteOffset + data.byteLength);
    const digest = await crypto.subtle.digest('SHA-256', bytes);
    return [...new Uint8Array(digest)].map((byte) => byte.toString(16).padStart(2, '0')).join('');
}

export async function verifyPayload(manifest, fetcher = fetch) {
    const asset = validateManifest(manifest).payloads[0];
    const response = await fetcher(`/${asset.filename}`, { cache: 'no-store' });
    if (! response.ok) throw new Error(`Laravel payload download failed (${response.status}).`);

    const payload = await response.arrayBuffer();
    if (payload.byteLength !== asset.bytes) throw new Error('Laravel payload size does not match the manifest.');
    if (await sha256Hex(payload) !== asset.sha256) throw new Error('Laravel payload checksum does not match the manifest.');

    return payload;
}

export function internalPath(pathname) {
    const path = pathname.startsWith(PHP_PREFIX) ? pathname.slice(PHP_PREFIX.length) : pathname;
    return `${PHP_PREFIX}${path === '/' ? '/demo/profiles' : path}`;
}
