import * as pdfjsLib from 'pdfjs-dist/build/pdf.min.mjs';
import workerSrc from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

const cfg = window.PDF_VIEWER_CONFIG ?? {};
const PDF_URL = cfg.streamUrl;
const USER_NAME = cfg.userName ?? 'Unknown';
const INFO_LINE = cfg.infoLine ?? '';

let pdfDoc = null;
let currentPage = 1;
let totalPages = 0;
let scale = 1.4;
let isFallback = false;
let qrImage = null;

if (cfg.qrDataUrl) {
    qrImage = new Image();
    qrImage.src = cfg.qrDataUrl;
}

const container = document.getElementById('viewer-container');
const loading = document.getElementById('loading');
const pageInfo = document.getElementById('page-info');

function addWatermarkOverlay(wrapper, width, height) {
    const overlay = document.createElement('canvas');
    overlay.style.position = 'absolute';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.pointerEvents = 'none';
    overlay.width = width;
    overlay.height = height;

    const ctx = overlay.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    ctx.scale(dpr, dpr);

    const cssW = width / dpr;
    const cssH = height / dpr;
    const mmToPx = scale * 72 / 25.4;
    const ptToPx = scale;

    // Primary diagonal watermark text (matches server-side drawPrimaryWatermarkText)
    // Server uses pt; in PDF.js 1 pt = 1 px at scale 1.0, so multiply by scale.
    ctx.save();
    ctx.translate(cssW / 2, cssH / 2);
    ctx.rotate(-35 * Math.PI / 180);
    ctx.globalAlpha = 0.25;
    ctx.fillStyle = 'rgb(190, 0, 0)';
    ctx.font = `bold ${46 * ptToPx}px sans-serif`;
    ctx.textAlign = 'center';
    ctx.fillText('PROPERTY OF INSTAT', 0, -6 * mmToPx);
    ctx.font = `bold ${32 * ptToPx}px sans-serif`;
    ctx.fillText('NOT TO BE REPRODUCED', 0, 10 * mmToPx);
    ctx.restore();

    // QR + info block at bottom-left (matches server-side drawQrAndInfoBlock)
    const qrSize = 12 * mmToPx;
    const edgeOffset = 0.5 * mmToPx;
    const blockHeight = 18 * mmToPx;
    const blockWidth = Math.max(72 * ptToPx, Math.min(cssW - (edgeOffset * 2), 112 * ptToPx));
    const startX = edgeOffset;
    const startY = cssH - blockHeight - edgeOffset;

    if (qrImage && qrImage.complete && qrImage.naturalWidth > 0) {
        ctx.drawImage(qrImage, startX + 2 * mmToPx, startY + 2 * mmToPx, qrSize, qrSize);
    }

    const infoLine = INFO_LINE || `${USER_NAME} · ${new Date().toISOString().slice(0, 19).replace('T', ' ')} PHT`;
    ctx.fillStyle = 'rgb(40, 40, 40)';
    ctx.font = `${7 * ptToPx}px sans-serif`;
    ctx.textAlign = 'left';
    // Match server-side TCPDF Cell() middle-vertical-align behaviour:
    // the font bounding box is centred in the 4 mm cell, not the baseline.
    ctx.textBaseline = 'middle';
    ctx.fillText(infoLine, startX + qrSize + 2 * mmToPx, startY + qrSize);
    ctx.textBaseline = 'alphabetic';

    wrapper.appendChild(overlay);
}

if (PDF_URL && container && loading && pageInfo) {
    fetch(PDF_URL, { credentials: 'include' })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            isFallback = response.headers.get('X-Watermark-Fallback') === 'true';
            return response.blob();
        })
        .then(blob => {
            const blobUrl = URL.createObjectURL(blob);
            const loadingTask = pdfjsLib.getDocument({
                url: blobUrl,
                withCredentials: false,
            });

            loadingTask.promise.then(pdf => {
                pdfDoc = pdf;
                totalPages = pdf.numPages;
                loading.remove();
                renderAll();
            }).catch(err => {
                loading.textContent = 'Failed to load document.';
                console.error(err);
            });
        })
        .catch(err => {
            loading.textContent = 'Failed to load document.';
            console.error(err);
        });
}

async function renderAll() {
    container.innerHTML = '';

    for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
        const page = await pdfDoc.getPage(pageNum);
        const wrapper = document.createElement('div');
        wrapper.className = 'page-wrapper';
        wrapper.style.position = 'relative';

        const pdfCanvas = document.createElement('canvas');
        pdfCanvas.className = 'pdf-canvas';

        wrapper.appendChild(pdfCanvas);
        container.appendChild(wrapper);

        await renderPage(page, pdfCanvas);

        if (isFallback) {
            addWatermarkOverlay(wrapper, pdfCanvas.width, pdfCanvas.height);
        }
    }

    pageInfo.textContent = `${totalPages} page${totalPages > 1 ? 's' : ''}`;
    updateCurrentPageInfo();
}

async function renderPage(page, pdfCanvas) {
    const dpr = window.devicePixelRatio || 1;
    const viewport = page.getViewport({ scale: scale * dpr });

    pdfCanvas.width = viewport.width;
    pdfCanvas.height = viewport.height;

    const cssW = viewport.width / dpr;
    const cssH = viewport.height / dpr;
    pdfCanvas.style.width = `${cssW}px`;
    pdfCanvas.style.height = `${cssH}px`;

    const ctx = pdfCanvas.getContext('2d');
    await page.render({ canvasContext: ctx, viewport }).promise;
}

function updateCurrentPageInfo() {
    if (!container) {
        return;
    }

    const wrappers = Array.from(container.querySelectorAll('.page-wrapper'));
    if (wrappers.length === 0) {
        return;
    }

    const containerTop = container.scrollTop;
    let nearestIndex = 0;
    let nearestDist = Number.POSITIVE_INFINITY;

    wrappers.forEach((el, i) => {
        const dist = Math.abs(el.offsetTop - containerTop);
        if (dist < nearestDist) {
            nearestDist = dist;
            nearestIndex = i;
        }
    });

    currentPage = nearestIndex + 1;
    pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
}

container?.addEventListener('scroll', updateCurrentPageInfo);

document.getElementById('btn-prev')?.addEventListener('click', () => {
    if (!container) {
        return;
    }

    const wrappers = container.querySelectorAll('.page-wrapper');
    if (currentPage > 1) {
        currentPage -= 1;
        wrappers[currentPage - 1]?.scrollIntoView({ behavior: 'smooth' });
        updateCurrentPageInfo();
    }
});

document.getElementById('btn-next')?.addEventListener('click', () => {
    if (!container) {
        return;
    }

    const wrappers = container.querySelectorAll('.page-wrapper');
    if (currentPage < totalPages) {
        currentPage += 1;
        wrappers[currentPage - 1]?.scrollIntoView({ behavior: 'smooth' });
        updateCurrentPageInfo();
    }
});

document.getElementById('btn-zoom-in')?.addEventListener('click', async () => {
    scale = Math.min(scale + 0.2, 3);
    await renderAll();
});

document.getElementById('btn-zoom-out')?.addEventListener('click', async () => {
    scale = Math.max(scale - 0.2, 0.6);
    await renderAll();
});

document.addEventListener('contextmenu', (event) => {
    event.preventDefault();
});

document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && ['s', 'p', 'u'].includes(event.key.toLowerCase())) {
        event.preventDefault();
    }

    if (event.metaKey && event.shiftKey && event.key.toLowerCase() === 's') {
        event.preventDefault();
    }

    if (event.key === 'PrintScreen') {
        event.preventDefault();
    }

    if (event.key === 'F12') {
        event.preventDefault();
    }
});
