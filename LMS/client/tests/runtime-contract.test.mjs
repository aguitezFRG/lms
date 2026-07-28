import assert from 'node:assert/strict';
import { webcrypto } from 'node:crypto';
import test from 'node:test';

import {
    internalPath,
    livewireRedirectPayload,
    sha256Hex,
    validateManifest,
    verifyPayload,
} from '../src/runtime-contract.js';

globalThis.crypto ??= webcrypto;

const payload = new TextEncoder().encode('native Laravel payload');
const manifest = async () => ({
    runtimeVersion: 1,
    schemaVersion: 2,
    phpVersion: '8.4',
    payloads: [{
        filename: 'laravel-demo-v1.zip',
        bytes: payload.byteLength,
        sha256: await sha256Hex(payload),
    }],
});

test('validates the pinned browser runtime manifest', async () => {
    const validManifest = await manifest();

    assert.equal(validateManifest(validManifest).phpVersion, '8.4');
    assert.throws(
        () => validateManifest({ ...validManifest, runtimeVersion: 2 }),
        /Unsupported browser runtime version/,
    );
});

test('normalizes public paths into the internal PHP prefix', () => {
    assert.equal(internalPath('/'), '/__php/demo/profiles');
    assert.equal(internalPath('/admin'), '/__php/admin');
    assert.equal(internalPath('/__php/app'), '/__php/app');
});

test('normalizes CGI redirects into Livewire redirect effects', () => {
    const snapshot = '{"data":{},"memo":{}}';
    const payload = livewireRedirectPayload(
        { components: [{ snapshot }] },
        '/__php/admin/users/123',
        'https://demo.example/__php/livewire-release/update',
    );

    assert.deepEqual(payload, {
        components: [{
            snapshot,
            effects: {
                redirect: 'https://demo.example/__php/admin/users/123',
                redirectUsingNavigate: false,
            },
        }],
        assets: [],
    });
});

test('verifies payload size and checksum before boot', async () => {
    const validManifest = await manifest();
    const fetcher = async () => new Response(payload);

    assert.deepEqual(new Uint8Array(await verifyPayload(validManifest, fetcher)), payload);

    await assert.rejects(
        verifyPayload({
            ...validManifest,
            payloads: [{ ...validManifest.payloads[0], sha256: '0'.repeat(64) }],
        }, fetcher),
        /checksum does not match/,
    );
});
