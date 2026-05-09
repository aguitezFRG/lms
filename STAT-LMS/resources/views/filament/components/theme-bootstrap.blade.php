<script>
    (function () {
        const storageKey = 'stat-lms-theme';
        const allowedThemes = ['light', 'dark', 'dark-oled', 'system'];
        const systemThemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

        const normalizeTheme = (value) => {
            if (value === 'oled') return 'dark-oled';

            return allowedThemes.includes(value) ? value : 'system';
        };

        const resolveTheme = (theme) => {
            if (theme === 'system') return systemThemeQuery.matches ? 'dark' : 'light';
            if (theme === 'dark-oled') return 'dark-oled';

            return theme;
        };

        const getFilamentTheme = (theme) => {
            if (theme === 'system') return 'system';

            return resolveTheme(theme) === 'light' ? 'light' : 'dark';
        };

        const syncFilamentTheme = (theme) => {
            const filamentTheme = getFilamentTheme(theme);
            const resolvedTheme = resolveTheme(theme);

            window.theme = filamentTheme;

            if (window.Alpine?.store) {
                window.Alpine.store('theme', resolvedTheme === 'light' ? 'light' : 'dark');
            }

            // Keep Filament's own localStorage key in sync so its alpine:init
            // handler reads the correct value and does not reset the html classes.
            try {
                localStorage.setItem('theme', filamentTheme);
            } catch (_) {}
        };

        const swapClasses = (theme) => {
            const resolvedTheme = resolveTheme(theme);
            const root = document.documentElement;

            root.classList.remove('dark', 'oled');
            if (resolvedTheme === 'dark') root.classList.add('dark');
            if (resolvedTheme === 'dark-oled') root.classList.add('dark', 'oled');
            localStorage.setItem(storageKey, theme);
            syncFilamentTheme(theme);
            window.dispatchEvent(new CustomEvent('stat-lms-theme:changed', { detail: { theme, resolvedTheme } }));
        };

        const applyTheme = (value, animate = false) => {
            const theme = normalizeTheme(value);
            const resolvedTheme = resolveTheme(theme);

            if (!animate || !document.body) {
                swapClasses(theme);
                return;
            }

            const isDark = resolvedTheme !== 'light';
            const overlay = document.createElement('div');
            overlay.style.cssText =
                'position:fixed;inset:0;z-index:2147483647;pointer-events:none;' +
                'background:' + (isDark ? '#000' : '#fff') + ';' +
                'opacity:0;transition:opacity 130ms ease;';
            document.body.appendChild(overlay);

            // Double rAF ensures the browser starts the fade-in before we set opacity.
            requestAnimationFrame(() => requestAnimationFrame(() => {
                overlay.style.opacity = '0.94';
            }));

            // Swap theme while overlay is opaque, then fade out.
            setTimeout(() => {
                swapClasses(theme);
                overlay.style.opacity = '0';
                setTimeout(() => overlay.remove(), 200);
            }, 150);
        };

        window.statLmsTheme = {
            apply: (value) => applyTheme(value, true),
            get: function () {
                return normalizeTheme(localStorage.getItem(storageKey) ?? localStorage.getItem('theme'));
            },
        };

        applyTheme(localStorage.getItem(storageKey) ?? localStorage.getItem('theme'));

        systemThemeQuery.addEventListener('change', () => {
            if (window.statLmsTheme.get() === 'system') {
                applyTheme('system');
            }
        });

        document.addEventListener('alpine:init', () => {
            syncFilamentTheme(window.statLmsTheme.get());
        });
    })();
</script>
