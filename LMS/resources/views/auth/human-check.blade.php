<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Human verification | LMS</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @include('filament.components.theme-bootstrap')
    <script>
        let lmsTurnstileWidgetId = null;
        let lmsTurnstileSubmissionStarted = false;
        const lmsTurnstileCompactQuery = window.matchMedia('(max-width: 480px)');

        const getLmsTurnstileTheme = () => document.documentElement.classList.contains('dark')
            ? 'dark'
            : 'light';

        const getLmsTurnstileSize = () => lmsTurnstileCompactQuery.matches
            ? 'compact'
            : 'flexible';

        const submitLmsTurnstileForm = () => {
            if (lmsTurnstileSubmissionStarted) return;

            const form = document.getElementById('turnstile-form');

            if (! form) return;

            lmsTurnstileSubmissionStarted = true;
            document.getElementById('turnstile-status').textContent = 'Verification complete. Continuing…';
            form.requestSubmit();
        };

        const renderLmsTurnstile = () => {
            if (! window.turnstile) return;

            if (lmsTurnstileWidgetId !== null) {
                window.turnstile.remove(lmsTurnstileWidgetId);
            }

            lmsTurnstileWidgetId = window.turnstile.render('#turnstile-widget', {
                sitekey: @js($siteKey),
                action: @js($action),
                theme: getLmsTurnstileTheme(),
                size: getLmsTurnstileSize(),
                callback: submitLmsTurnstileForm,
            });
        };

        window.lmsTurnstileReady = renderLmsTurnstile;
        window.addEventListener('lms-theme:changed', renderLmsTurnstile);
        lmsTurnstileCompactQuery.addEventListener('change', renderLmsTurnstile);
    </script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=lmsTurnstileReady&render=explicit" defer></script>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        html { height: 100%; }
        html.dark { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { position: fixed; inset: 0; width: 100%; height: 100%; margin: 0; display: flex; overflow-y: auto; padding: 1.5rem; background: #f1f5f9; color: #0f172a; }
        main { flex: 0 0 auto; width: min(100%, 28rem); margin: auto; padding: 2rem; border: 1px solid #e2e8f0; border-radius: 1.25rem; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, .12); text-align: center; }
        img { width: 5rem; height: 5rem; border-radius: 1rem; }
        h1 { margin: 1rem 0 .5rem; font-size: 1.65rem; }
        p { margin: 0 0 1.5rem; color: #475569; line-height: 1.55; }
        form { display: grid; min-width: 0; gap: 1rem; }
        #turnstile-widget { display: flex; width: 100%; min-width: 0; min-height: 65px; align-items: center; justify-content: center; }
        #turnstile-widget > div,
        #turnstile-widget iframe { max-width: 100%; }
        #turnstile-widget iframe { display: block; }
        .status { min-height: 1.25rem; color: #475569; font-size: .9rem; }
        .error { padding: .75rem; border-radius: .75rem; background: #fff1f2; color: #9f1239; font-size: .9rem; }
        small { display: block; margin-top: 1.25rem; color: #64748b; }
        html.dark body { background: #020617; color: #f8fafc; }
        html.dark main { border-color: #334155; background: #0f172a; box-shadow: none; }
        html.dark p, html.dark small, html.dark .status { color: #cbd5e1; }
        html.dark .error { background: #4c0519; color: #fecdd3; }
        html.dark.oled body { background: #000; }
        html.dark.oled main { border-color: #27272a; background: #030303; }
    </style>
</head>
<body>
    <main>
        <img src="/lms_favicon.png" alt="LMS logo">
        <h1>Confirm you are human</h1>
        <p>Complete this quick security check to continue to the LMS demo.</p>

        @if ($errors->any())
            <div class="error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form id="turnstile-form" method="POST" action="{{ route('turnstile.verify') }}">
            @csrf
            <div id="turnstile-widget"></div>
            <div id="turnstile-status" class="status" role="status" aria-live="polite"></div>
        </form>

        <small>Protected by Cloudflare Turnstile</small>
    </main>
</body>
</html>
