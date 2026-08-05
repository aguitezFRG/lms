<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Service temporarily unavailable</title>
    <style>
        :root { color-scheme: light; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f8fafc; color: #0f172a; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; padding: 1.5rem; background: radial-gradient(circle at top, rgba(22, 163, 74, 0.12), transparent 35rem), #f8fafc; }
        main { width: min(100%, 38rem); padding: clamp(2rem, 6vw, 3.5rem); border: 1px solid #e2e8f0; border-radius: 1.5rem; background: rgba(255, 255, 255, 0.96); box-shadow: 0 1.5rem 4rem rgba(15, 23, 42, 0.1); text-align: center; }
        .status { display: inline-flex; align-items: center; gap: .5rem; margin-bottom: 1.25rem; padding: .45rem .8rem; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: .875rem; font-weight: 700; }
        .status::before { content: ""; width: .55rem; height: .55rem; border-radius: 999px; background: #f59e0b; }
        h1 { margin: 0; font-size: clamp(2rem, 7vw, 3.25rem); line-height: 1.05; letter-spacing: -.04em; }
        p { margin: 1rem auto 0; max-width: 31rem; color: #475569; font-size: 1.05rem; line-height: 1.7; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin-top: 2rem; }
        a, button { min-height: 2.75rem; display: inline-flex; align-items: center; justify-content: center; padding: .7rem 1rem; border: 1px solid #cbd5e1; border-radius: .75rem; background: #fff; color: #0f172a; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        button { border-color: #15803d; background: #15803d; color: #fff; }
        a:focus-visible, button:focus-visible { outline: 3px solid rgba(22, 163, 74, .35); outline-offset: 3px; }
        small { display: block; margin-top: 1.5rem; color: #64748b; line-height: 1.5; }
    </style>
</head>
<body>
    <main>
        <div class="status">503 · Temporary outage</div>
        <h1>The library service is temporarily unavailable.</h1>
        <p>We cannot connect to the database right now. Your request was not completed. Please try again shortly.</p>
        <div class="actions">
            <button type="button" onclick="window.location.reload()">Try again</button>
            <a href="/">Return to home</a>
        </div>
        <small>If the issue continues, the service may still be recovering.</small>
    </main>
</body>
</html>
