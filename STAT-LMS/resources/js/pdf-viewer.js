import * as pdfjsLib from 'pdfjs-dist/build/pdf.min.mjs';
import workerSrc from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

const cfg = window.PDF_VIEWER_CONFIG ?? {};
const PDF_URL = cfg.streamUrl;
const USER_NAME = cfg.userName;
const USER_ID = cfg.userId;
const TIMESTAMP = cfg.timestamp;

let pdfDoc = null;
let currentPage = 1;
let totalPages = 0;
let scale = 1.4;

const container = document.getElementById('viewer-container');
const loading = document.getElementById('loading');
const pageInfo = document.getElementById('page-info');

if (PDF_URL && container && loading && pageInfo) {
    const loadingTask = pdfjsLib.getDocument({
        url: PDF_URL,
        withCredentials: true,
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
}

async function renderAll() {
    container.innerHTML = '';

    for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
        const page = await pdfDoc.getPage(pageNum);
        const wrapper = document.createElement('div');
        wrapper.className = 'page-wrapper';

        const pdfCanvas = document.createElement('canvas');
        pdfCanvas.className = 'pdf-canvas';

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

async function renderPage(page, pdfCanvas, wmCanvas) {
    const dpr = window.devicePixelRatio || 1;
    const viewport = page.getViewport({ scale: scale * dpr });

    pdfCanvas.width = viewport.width;
    pdfCanvas.height = viewport.height;
    wmCanvas.width = viewport.width;
    wmCanvas.height = viewport.height;

    const cssW = viewport.width / dpr;
    const cssH = viewport.height / dpr;
    pdfCanvas.style.width = `${cssW}px`;
    pdfCanvas.style.height = `${cssH}px`;
    wmCanvas.style.width = `${cssW}px`;
    wmCanvas.style.height = `${cssH}px`;

    const ctx = pdfCanvas.getContext('2d');
    await page.render({ canvasContext: ctx, viewport }).promise;
    drawWatermark(wmCanvas);
}

function drawWatermark(canvas) {
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const w = canvas.width;
    const h = canvas.height;

    ctx.save();
    ctx.globalAlpha = 0.06;
    ctx.fillStyle = '#000000';
    ctx.font = '28px Arial';

    const diagonalText = `${USER_NAME} | ${USER_ID}`;
    const step = 260;

    ctx.translate(w / 2, h / 2);
    ctx.rotate((-32 * Math.PI) / 180);

    for (let y = -h; y < h; y += step) {
        for (let x = -w; x < w; x += step) {
            ctx.fillText(diagonalText, x, y);
        }
    }

    ctx.restore();

    ctx.save();
    ctx.globalAlpha = 0.14;
    ctx.fillStyle = '#8D1436';
    ctx.font = 'bold 22px Arial';
    ctx.textAlign = 'center';

    ctx.fillText('UP INSTAT Reading Room • Confidential Access', w / 2, h * 0.12);

    ctx.globalAlpha = 0.1;
    ctx.fillStyle = '#014421';
    ctx.font = '16px Arial';
    ctx.fillText(`Viewed by ${USER_NAME} (${USER_ID})`, w / 2, h * 0.9);
    ctx.fillText(`Timestamp: ${TIMESTAMP}`, w / 2, h * 0.94);

    ctx.restore();
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
