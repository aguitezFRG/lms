<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Human verification | LMS</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        :root { color-scheme: light dark; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 1.5rem; background: #f1f5f9; color: #0f172a; }
        main { width: min(100%, 28rem); padding: 2rem; border: 1px solid #e2e8f0; border-radius: 1.25rem; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, .12); text-align: center; }
        img { width: 5rem; height: 5rem; border-radius: 1rem; }
        h1 { margin: 1rem 0 .5rem; font-size: 1.65rem; }
        p { margin: 0 0 1.5rem; color: #475569; line-height: 1.55; }
        form { display: grid; gap: 1rem; }
        .cf-turnstile { width: 100%; min-height: 65px; }
        button { width: 100%; border: 0; border-radius: .75rem; padding: .8rem 1rem; background: #014421; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: #02582b; }
        .error { padding: .75rem; border-radius: .75rem; background: #fff1f2; color: #9f1239; font-size: .9rem; }
        small { display: block; margin-top: 1.25rem; color: #64748b; }
        @media (prefers-color-scheme: dark) {
            body { background: #020617; color: #f8fafc; }
            main { border-color: #334155; background: #0f172a; box-shadow: none; }
            p, small { color: #cbd5e1; }
            .error { background: #4c0519; color: #fecdd3; }
        }
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

        <form method="POST" action="{{ route('turnstile.verify') }}">
            @csrf
            <div
                class="cf-turnstile"
                data-sitekey="{{ $siteKey }}"
                data-action="{{ $action }}"
                data-theme="auto"
                data-size="flexible"
            ></div>
            <button type="submit">Continue to LMS</button>
        </form>

        <small>Protected by Cloudflare Turnstile</small>
    </main>
</body>
</html>
