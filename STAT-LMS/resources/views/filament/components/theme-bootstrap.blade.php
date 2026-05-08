<script>
    (function () {
        const storageKey = 'stat-lms-theme';
        const allowedThemes = ['light', 'dark', 'oled'];

        const normalizeTheme = (value) => allowedThemes.includes(value) ? value : 'light';

        const syncFilamentTheme = (theme) => {
            const filamentTheme = theme === 'light' ? 'light' : 'dark';

            window.theme = filamentTheme;

            if (window.Alpine?.store) {
                window.Alpine.store('theme', filamentTheme);
            }

            // Keep Filament's own localStorage key in sync so its alpine:init
            // handler reads the correct value and does not reset the html classes.
            try {
                localStorage.setItem('theme', filamentTheme);
            } catch (_) {}
        };

        const swapClasses = (theme) => {
            const root = document.documentElement;
            root.classList.remove('dark', 'oled');
            if (theme === 'dark') root.classList.add('dark');
            if (theme === 'oled') root.classList.add('dark', 'oled');
            localStorage.setItem(storageKey, theme);
            syncFilamentTheme(theme);
            window.dispatchEvent(new CustomEvent('stat-lms-theme:changed', { detail: { theme } }));
        };

        const applyTheme = (value, animate = false) => {
            const theme = normalizeTheme(value);

            if (!animate || !document.body) {
                swapClasses(theme);
                return;
            }

            const isDark = theme === 'dark' || theme === 'oled';
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
                return normalizeTheme(localStorage.getItem(storageKey));
            },
        };

        applyTheme(localStorage.getItem(storageKey));

        document.addEventListener('alpine:init', () => {
            syncFilamentTheme(normalizeTheme(localStorage.getItem(storageKey)));
        });
    })();
</script>
