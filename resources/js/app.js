import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

function addWebsitePreviewScreenshotNote() {
    const previewHeading = Array.from(document.querySelectorAll('p')).find((element) => {
        return element.textContent?.trim().toLowerCase() === 'website preview';
    });

    const previewCard = previewHeading?.closest('section');

    if (! previewCard || previewCard.querySelector('[data-qsa-screenshot-note]')) {
        return;
    }

    const browserPreview = previewCard.querySelector('.bg-white.p-5');

    if (! browserPreview) {
        return;
    }

    const note = document.createElement('div');
    note.dataset.qsaScreenshotNote = 'true';
    note.className = 'mt-3 rounded-lg bg-blue-50 p-4 text-sm font-semibold leading-6 text-blue-900 ring-1 ring-blue-100';
    note.textContent = 'Screenshot capture coming soon. This preview currently shows the scanned URL, page title, meta description and HTTPS status.';

    browserPreview.appendChild(note);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addWebsitePreviewScreenshotNote);
} else {
    addWebsitePreviewScreenshotNote();
}
