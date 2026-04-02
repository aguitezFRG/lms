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
            align-items: center;
            padding: 24px 16px;
            gap: 16px;
        }

        .page-wrapper {
            position: relative;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.5);
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

    {{-- ── PDF.js from CDN (pinned version) ─────────────────────── --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.3.136/pdf.min.mjs" type="module"></script>

    <script type="module">
        import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.3.136/pdf.min.mjs';

        // Point worker at the same CDN version
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.3.136/pdf.worker.min.mjs';

        // ── Config (injected from Laravel) ────────────────────────
        const PDF_URL   = @json($streamUrl);
        const USER_NAME = @json($user->name);
        const USER_ID   = @json($user->id);
        const TIMESTAMP = @json(now()->format('Y-m-d H:i'));

        // ── State ─────────────────────────────────────────────────
        let pdfDoc      = null;
        let currentPage = 1;
        let totalPages  = 0;
        let scale       = 1.4;   // initial zoom

        const container = document.getElementById('viewer-container');
        const loading   = document.getElementById('loading');
        const pageInfo  = document.getElementById('page-info');

        // ── Load PDF ──────────────────────────────────────────────
        const loadingTask = pdfjsLib.getDocument({
            url: PDF_URL,
            // Credentials are required so Laravel's session cookie is sent
            withCredentials: true,
        });

        loadingTask.promise.then(pdf => {
            pdfDoc     = pdf;
            totalPages = pdf.numPages;
            loading.remove();
            renderAll();
        }).catch(err => {
            loading.textContent = 'Failed to load document.';
            console.error(err);
        });

        // ── Render every page at once (scroll-based) ──────────────
        async function renderAll() {
            container.innerHTML = '';

            for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
                const page    = await pdfDoc.getPage(pageNum);
                const wrapper = document.createElement('div');
                wrapper.className = 'page-wrapper';

                // PDF canvas
                const pdfCanvas = document.createElement('canvas');
                pdfCanvas.className = 'pdf-canvas';

                // Watermark canvas (overlay)
                const wmCanvas = document.createElement('canvas');
                wmCanvas.className = 'wm-canvas';

                wrapper.appendChild(pdfCanvas);
                wrapper.appendChild(wmCanvas);
                container.appendChild(wrapper);

                await renderPage(page, pdfCanvas, wmCanvas);
            }

            pageInfo.textContent = `${totalPages} page${totalPages > 1 ? 's' : ''}`;
            updateCurrentPageInfo();
        }

        // ── Render a single page ──────────────────────────────────
        async function renderPage(page, pdfCanvas, wmCanvas) {
            const viewport = page.getViewport({ scale });
            const ctx      = pdfCanvas.getContext('2d');

            pdfCanvas.width  = viewport.width;
            pdfCanvas.height = viewport.height;
            wmCanvas.width   = viewport.width;
            wmCanvas.height  = viewport.height;

            // Render PDF content
            await page.render({ canvasContext: ctx, viewport }).promise;

            // Draw watermark on top
            drawWatermark(wmCanvas, viewport);
        }

        // ── Watermark drawing ─────────────────────────────────────
        function drawWatermark(canvas, viewport) {
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const w = canvas.width;
            const h = canvas.height;

            // ── 1. Diagonal tiled text (scrambler layer) ──────────
            ctx.save();
            ctx.globalAlpha = 0.06;
            ctx.fillStyle   = '#000000';
            ctx.font        = 'bold 11px monospace';

            const step = 40;
            for (let y = 0; y < h + step; y += step) {
                for (let x = 0; x < w + step; x += step) {
                    ctx.save();
                    ctx.translate(x, y);
                    ctx.rotate(-Math.PI / 4);
                    ctx.fillText('INSTAT-RR-SPRIS', 0, 0);
                    ctx.restore();
                }
            }
            ctx.restore();

            // ── 2. User identity strip at the bottom ──────────────
            ctx.save();
            ctx.globalAlpha  = 0.45;
            ctx.fillStyle    = '#555555';
            ctx.font         = `10px monospace`;
            ctx.textBaseline = 'bottom';
            const label = `${USER_NAME}  •  ${USER_ID}  •  ${TIMESTAMP}`;
            ctx.fillText(label, 10, h - 8);
            ctx.restore();

            // ── 3. Center semi-transparent diagonal stamp ─────────
            ctx.save();
            ctx.globalAlpha = 0.10;
            ctx.fillStyle   = '#8D1436'; // UP Maroon
            ctx.font        = `bold ${Math.floor(w / 12)}px sans-serif`;
            ctx.textAlign   = 'center';
            ctx.textBaseline = 'middle';
            ctx.translate(w / 2, h / 2);
            ctx.rotate(-Math.PI / 4);
            ctx.fillText('INSTAT-RR-SPRIS', 0, 0);
            ctx.restore();
        }

        // ── Zoom ──────────────────────────────────────────────────
        document.getElementById('btn-zoom-in').addEventListener('click', () => {
            scale = Math.min(scale + 0.2, 3.0);
            renderAll();
        });

        document.getElementById('btn-zoom-out').addEventListener('click', () => {
            scale = Math.max(scale - 0.2, 0.6);
            renderAll();
        });

        // ── Page navigation (scroll to page) ─────────────────────
        document.getElementById('btn-prev').addEventListener('click', () => scrollToPage(currentPage - 1));
        document.getElementById('btn-next').addEventListener('click', () => scrollToPage(currentPage + 1));

        function scrollToPage(num) {
            const wrappers = container.querySelectorAll('.page-wrapper');
            if (num < 1 || num > wrappers.length) return;
            wrappers[num - 1].scrollIntoView({ behavior: 'smooth' });
        }

        function updateCurrentPageInfo() {
            pageInfo.textContent = `Page ${currentPage} / ${totalPages}`;
        }

        // Track scroll position to update current page indicator
        container.addEventListener('scroll', () => {
            const wrappers = [...container.querySelectorAll('.page-wrapper')];
            const mid      = container.scrollTop + container.clientHeight / 2;
            let closest    = 1;
            wrappers.forEach((el, i) => {
                if (el.offsetTop <= mid) closest = i + 1;
            });
            if (closest !== currentPage) {
                currentPage = closest;
                updateCurrentPageInfo();
            }
        });

        // ── Block context menu / right-click ──────────────────────
        document.addEventListener('contextmenu', e => e.preventDefault());

        // ── Block common keyboard shortcuts for saving ────────────
        document.addEventListener('keydown', e => {
            // Ctrl/Cmd + S (save), P (print), U (view source)
            if ((e.ctrlKey || e.metaKey) && ['s', 'p', 'u'].includes(e.key.toLowerCase())) {
                e.preventDefault();
            }

            // Windows + Shift + S (Snipping Tool)
            if (e.metaKey && e.shiftKey && e.key.toLowerCase() === 's') {
                e.preventDefault();
            }

            // Print Screen key
            if (e.key === 'PrintScreen') {
                e.preventDefault();
            }

            // F12 (DevTools)
            if (e.key === 'F12') {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>