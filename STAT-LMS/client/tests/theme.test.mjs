import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

import {
    STORAGE_KEY,
    normalizeTheme,
    resolveTheme,
} from '../src/theme.js';

test('uses the same persisted theme key as the Filament user page', () => {
    assert.equal(STORAGE_KEY, 'stat-lms-theme');
});

test('normalizes all supported user-page modes', () => {
    assert.equal(normalizeTheme('light'), 'light');
    assert.equal(normalizeTheme('dark'), 'dark');
    assert.equal(normalizeTheme('dark-oled'), 'dark-oled');
    assert.equal(normalizeTheme('system'), 'system');
    assert.equal(normalizeTheme('oled'), 'dark-oled');
    assert.equal(normalizeTheme('unknown'), 'system');
});

test('resolves system and OLED modes for setup styling', () => {
    assert.equal(resolveTheme('system', true), 'dark');
    assert.equal(resolveTheme('system', false), 'light');
    assert.equal(resolveTheme('dark-oled', false), 'dark-oled');
});

test('uses only solid backgrounds on the LMS demo startup page', () => {
    const styles = readFileSync(new URL('../src/styles.css', import.meta.url), 'utf8');

    assert.doesNotMatch(styles, /(?:linear|radial|conic)-gradient\s*\(/i);
    assert.match(styles, /\.startup\s*\{[^}]*background:\s*#f4f6f8;/s);
    assert.match(styles, /html\.dark \.startup\s*\{[^}]*background:\s*#0f172a;/s);
    assert.match(styles, /html\.oled\.dark \.startup\s*\{\s*background:\s*#000;/s);
});
