/**
 * AGF Page Transitions — View Transitions API + Fallback
 * Morphs shared elements (avatars, titles) between page navigations.
 * Falls back to cross-fade on unsupported browsers.
 */

const AGFTransitions = (() => {
    const supportsVT = 'startViewTransition' in document;

    // Assign view-transition-name to shared elements on current page
    function tagSharedElements() {
        // User avatars
        document.querySelectorAll('[data-vt-avatar]').forEach(el => {
            el.style.viewTransitionName = 'avatar-' + el.dataset.vtAvatar;
        });
        // Thread titles
        document.querySelectorAll('[data-vt-title]').forEach(el => {
            el.style.viewTransitionName = 'title-' + el.dataset.vtTitle;
        });
        // Main content area
        const main = document.querySelector('main');
        if (main) main.style.viewTransitionName = 'main-content';
    }

    // Navigate with transition
    function navigate(url) {
        if (supportsVT) {
            document.startViewTransition(() => {
                return new Promise(resolve => {
                    fetch(url)
                        .then(r => r.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc    = parser.parseFromString(html, 'text/html');
                            const newMain = doc.querySelector('main');
                            const curMain = document.querySelector('main');
                            if (newMain && curMain) {
                                curMain.replaceWith(newMain);
                            }
                            // Update <title>
                            document.title = doc.title;
                            // Update URL
                            history.pushState({}, '', url);
                            tagSharedElements();
                            resolve();
                        })
                        .catch(() => { window.location.href = url; resolve(); });
                });
            });
        } else {
            // Fallback: opacity cross-fade
            document.body.animate(
                [{ opacity: 1 }, { opacity: 0 }],
                { duration: 180, easing: 'ease-in', fill: 'forwards' }
            ).onfinish = () => { window.location.href = url; };
        }
    }

    // Intercept all internal <a> clicks
    function init() {
        tagSharedElements();

        document.addEventListener('click', e => {
            const a = e.target.closest('a');
            if (!a) return;
            const href = a.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('http') || href.startsWith('mailto') || href.startsWith('tel')) return;
            if (a.target === '_blank') return;
            if (a.dataset.noTransition !== undefined) return;
            e.preventDefault();
            navigate(href);
        });

        // Handle browser back/forward
        window.addEventListener('popstate', () => {
            navigate(location.pathname + location.search);
        });
    }

    return { init, navigate, tagSharedElements };
})();

document.addEventListener('DOMContentLoaded', () => AGFTransitions.init());
window.AGFTransitions = AGFTransitions;
