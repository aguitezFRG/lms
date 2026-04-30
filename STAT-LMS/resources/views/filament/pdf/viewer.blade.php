<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title }} — INSTAT-RR-SPRIS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #1a1a1a;
            font-family: 'Helvetica Neue', sans-serif;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        /* ── Top bar ─────────────────────────────────────────────── */
        #toolbar {
            background: #2d2d2d;
            color: #e0e0e0;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid #444;
            flex-shrink: 0;
            user-select: none;
        }

        #toolbar .title {
            flex: 1;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #toolbar button {
            background: #444;
            border: none;
            color: #e0e0e0;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }

        #toolbar button:hover { background: #555; }

        #page-info {
            font-size: 13px;
            color: #aaa;
            white-space: nowrap;
        }

        /* ── Viewer area ─────────────────────────────────────────── */
        #viewer-container {
            flex: 1;
            overflow-y: auto;
            overflow-x: auto;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 24px 16px;
            gap: 16px;
        }

        .page-wrapper {
            position: relative;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
            margin: 0 auto;
        }

        /* The rendered PDF page */
        .page-wrapper canvas.pdf-canvas {
            display: block;
        }

        /* Watermark overlay — sits on top of the PDF canvas */
        .page-wrapper canvas.wm-canvas {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none; /* clicks pass through to PDF canvas */
        }

        /* ── Loading state ───────────────────────────────────────── */
        #loading {
            color: #aaa;
            font-size: 15px;
            margin-top: 40px;
        }
    </style>
</head>
<body>

    {{-- ── Toolbar ──────────────────────────────────────────────── --}}
    <div id="toolbar">
        <span class="title">📄 {{ $title }}</span>
        <span id="page-info">Loading…</span>
        <button id="btn-prev">◀ Prev</button>
        <button id="btn-next">Next ▶</button>
        <button id="btn-zoom-out">−</button>
        <button id="btn-zoom-in">+</button>
    </div>

    {{-- ── PDF Canvas Area ──────────────────────────────────────── --}}
    <div id="viewer-container">
        <div id="loading">Loading document…</div>
    </div>

    <script>
        window.PDF_VIEWER_CONFIG = {
            streamUrl: @json($streamUrl),
            userName: @json($user->name),
            userId: @json($user->id),
            timestamp: @json(now()->format('Y-m-d H:i')),
        };
    </script>
    @vite('resources/js/pdf-viewer.js')
</body>
</html>
