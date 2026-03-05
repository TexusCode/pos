import './bootstrap';

const swUrlMeta = document.querySelector('meta[name="pos-sw-url"]');
const swScopeMeta = document.querySelector('meta[name="pos-sw-scope"]');
const pwaEnabledMeta = document.querySelector('meta[name="pos-pwa-enabled"]');
const swUrl = swUrlMeta?.content || '/sw.js';
const swScope = swScopeMeta?.content || '/';
const pwaEnabled = pwaEnabledMeta?.content === '1';

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
    if (!pwaEnabled) {
        navigator.serviceWorker.getRegistrations().then((registrations) => {
            registrations.forEach((registration) => {
                registration.unregister();
            });
        });
    }

    window.addEventListener('load', async () => {
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
