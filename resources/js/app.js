import './bootstrap';

const basePathMeta = document.querySelector('meta[name="pos-base-path"]');
const pwaEnabledMeta = document.querySelector('meta[name="pos-pwa-enabled"]');
const basePath = (basePathMeta?.content || '').replace(/\/$/, '');
const pwaEnabled = pwaEnabledMeta?.content === '1';

const syncOfflineIndicator = () => {
    const indicator = document.getElementById('offline-indicator');

    if (!indicator) {
        return;
    }

    if (!pwaEnabled) {
        indicator.classList.add('hidden');
        return;
    }

    if (navigator.onLine) {
        indicator.classList.add('hidden');
    } else {
        indicator.classList.remove('hidden');
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

        const swUrl = `${basePath}/sw.js`;
        const swScope = `${basePath || ''}/`;

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
