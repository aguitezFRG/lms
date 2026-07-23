import assert from 'node:assert/strict';
import test from 'node:test';

import { isExpectedPhpWasmEvalWarning } from '../vite.config.js';

test('filters only eval warnings emitted by the pinned PHP-WASM worker', () => {
    assert.equal(isExpectedPhpWasmEvalWarning({
        code: 'EVAL',
        id: '/project/node_modules/php-cgi-wasm/php8.4-cgi-worker.mjs',
    }), true);
    assert.equal(isExpectedPhpWasmEvalWarning({
        code: 'EVAL',
        id: String.raw`C:\project\node_modules\php-cgi-wasm\php8.4-cgi-worker.mjs`,
    }), true);
});

test('preserves unrelated build warnings', () => {
    assert.equal(isExpectedPhpWasmEvalWarning({
        code: 'EVAL',
        id: '/project/src/runtime-worker.js',
    }), false);
    assert.equal(isExpectedPhpWasmEvalWarning({
        code: 'CIRCULAR_DEPENDENCY',
        id: '/project/node_modules/php-cgi-wasm/php8.4-cgi-worker.mjs',
    }), false);
    assert.equal(isExpectedPhpWasmEvalWarning(null), false);
});
