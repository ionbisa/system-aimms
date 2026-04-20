import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const body = document.body;
const loader = document.querySelector('[data-page-loader]');
let loaderTimer;

const showPageLoader = () => {
    if (!body || !loader) {
        return;
    }

    body.classList.add('page-loading');
    loader.classList.add('is-visible');
    loader.setAttribute('aria-hidden', 'false');
};

const hidePageLoader = () => {
    if (!body || !loader) {
        return;
    }

    window.clearTimeout(loaderTimer);
    body.classList.remove('page-loading');
    loader.classList.remove('is-visible');
    loader.setAttribute('aria-hidden', 'true');
};

const queuePageLoader = (delay = 45) => {
    window.clearTimeout(loaderTimer);
    loaderTimer = window.setTimeout(showPageLoader, delay);
};

const isNavigableLink = (link) => {
    if (!link) {
        return false;
    }

    const href = link.getAttribute('href');

    if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
        return false;
    }

    if (link.target && link.target !== '_self') {
        return false;
    }

    if (link.hasAttribute('download') || link.dataset.bsToggle) {
        return false;
    }

    const url = new URL(link.href, window.location.href);

    return url.origin === window.location.origin;
};

window.showPageLoader = showPageLoader;
window.hidePageLoader = hidePageLoader;

window.addEventListener('load', () => {
    window.setTimeout(hidePageLoader, 35);
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        hidePageLoader();
    }
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('a');

    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
    }

    if (isNavigableLink(link)) {
        queuePageLoader();
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
        return;
    }

    if (form.hasAttribute('data-skip-loader')) {
        return;
    }

    queuePageLoader(30);
});
