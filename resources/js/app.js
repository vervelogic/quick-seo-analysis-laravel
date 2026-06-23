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

function loadKeywordFocusReportSection() {
    if (! /^\/report\/[^/]+$/.test(window.location.pathname) || document.querySelector('[data-keyword-focus-audit]')) {
        return;
    }

    fetch(`${window.location.pathname}/keyword-focus`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
        .then((response) => (response.status === 204 ? '' : response.text()))
        .then((html) => {
            if (! html.trim() || document.querySelector('[data-keyword-focus-audit]')) {
                return;
            }

            const template = document.createElement('template');
            template.innerHTML = html.trim();
            const section = template.content.firstElementChild;

            if (! section) {
                return;
            }

            const executiveLabel = Array.from(document.querySelectorAll('p')).find((element) => {
                return element.textContent?.trim().toLowerCase() === 'executive summary';
            });
            const executiveSection = executiveLabel?.closest('section');

            if (executiveSection) {
                executiveSection.insertAdjacentElement('afterend', section);
                return;
            }

            document.querySelector('main')?.appendChild(section);
        })
        .catch(() => {
            // Keep the existing report fully usable if the optional section cannot load.
        });
}

function onReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback);
        return;
    }

    callback();
}

onReady(() => {
    addWebsitePreviewScreenshotNote();
    loadKeywordFocusReportSection();
});
