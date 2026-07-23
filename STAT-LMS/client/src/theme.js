export const STORAGE_KEY = 'stat-lms-theme';

const ALLOWED_THEMES = ['light', 'dark', 'dark-oled', 'system'];

export function normalizeTheme(value) {
    if (value === 'oled') return 'dark-oled';

    return ALLOWED_THEMES.includes(value) ? value : 'system';
}

export function resolveTheme(theme, prefersDark) {
    const normalizedTheme = normalizeTheme(theme);

    if (normalizedTheme === 'system') return prefersDark ? 'dark' : 'light';

    return normalizedTheme;
}

export function readStoredTheme(storage) {
    try {
        return normalizeTheme(storage.getItem(STORAGE_KEY) ?? storage.getItem('theme'));
    } catch {
        return 'system';
    }
}

export function applyTheme(theme, root, prefersDark) {
    const normalizedTheme = normalizeTheme(theme);
    const resolvedTheme = resolveTheme(normalizedTheme, prefersDark);

    root.classList.remove('dark', 'oled');
    if (resolvedTheme !== 'light') root.classList.add('dark');
    if (resolvedTheme === 'dark-oled') root.classList.add('oled');
    root.dataset.theme = normalizedTheme;

    return { theme: normalizedTheme, resolvedTheme };
}
