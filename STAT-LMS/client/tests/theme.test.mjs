import assert from 'node:assert/strict';
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
