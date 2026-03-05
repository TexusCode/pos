import './bootstrap';

const swUrlMeta = document.querySelector('meta[name="pos-sw-url"]');
const swScopeMeta = document.querySelector('meta[name="pos-sw-scope"]');
const pwaEnabledMeta = document.querySelector('meta[name="pos-pwa-enabled"]');
const swUrl = swUrlMeta?.content || '/sw.js';
const swScope = swScopeMeta?.content || '/';
const pwaEnabled = pwaEnabledMeta?.content === '1';
const normalizePath = (url) => {
    try {
        const parsed = new URL(url, window.location.origin);
        const pathname = parsed.pathname.replace(/\/+$/, '');
        return pathname === '' ? '/' : pathname;
    } catch {
        return '';
    }
};

const syncOfflineIndicator = () => {
    const indicator = document.getElementById('offline-indicator');

    if (!indicator) {
        return;
    }

    const hideIndicator = () => {
        indicator.style.display = 'none';
        indicator.setAttribute('hidden', 'hidden');
    };

    const showIndicator = () => {
        indicator.style.display = 'block';
        indicator.removeAttribute('hidden');
    };

    if (!pwaEnabled) {
        hideIndicator();
        return;
    }

    if (navigator.onLine) {
        hideIndicator();
    } else {
        showIndicator();
    }
};

window.addEventListener('online', syncOfflineIndicator);
window.addEventListener('offline', syncOfflineIndicator);
window.addEventListener('load', syncOfflineIndicator);

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        try {
            const registrations = await navigator.serviceWorker.getRegistrations();
            const expectedScope = normalizePath(swScope);
            const expectedScript = normalizePath(swUrl);

            await Promise.all(
                registrations.map(async (registration) => {
                    const scriptUrl =
                        registration.active?.scriptURL ||
                        registration.waiting?.scriptURL ||
                        registration.installing?.scriptURL ||
                        '';

                    const registrationScope = normalizePath(registration.scope);
                    const registrationScript = normalizePath(scriptUrl);
                    const scopeMatches = registrationScope === expectedScope;
                    const scriptMatches = registrationScript === expectedScript;

                    if (!pwaEnabled || !scopeMatches || !scriptMatches) {
                        await registration.unregister();
                    }
                }),
            );
        } catch (error) {
            console.error('Service worker cleanup failed:', error);
        }

        if (!pwaEnabled) {
            return;
        }

        try {
            const registration = await navigator.serviceWorker.register(swUrl, {
                scope: swScope,
            });

            if (registration.waiting) {
                registration.waiting.postMessage({ type: 'SKIP_WAITING' });
            }
        } catch (error) {
            console.error('Service worker registration failed:', error);
        }
    });
}
