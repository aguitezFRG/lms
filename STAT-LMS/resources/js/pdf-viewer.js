import * as pdfjsLib from 'pdfjs-dist/build/pdf.min.mjs';
import workerSrc from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerSrc;

const cfg = window.PDF_VIEWER_CONFIG ?? {};
const PDF_URL = cfg.streamUrl;

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

        wrapper.appendChild(pdfCanvas);
        container.appendChild(wrapper);

        await renderPage(page, pdfCanvas);
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
