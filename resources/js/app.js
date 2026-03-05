import './bootstrap';

const syncOfflineIndicator = () => {
    const indicator = document.getElementById('offline-indicator');

    if (!indicator) {
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
    window.addEventListener('load', async () => {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/',
            });

            if (registration.waiting) {
                registration.waiting.postMessage({ type: 'SKIP_WAITING' });
            }
        } catch (error) {
            console.error('Service worker registration failed:', error);
        }
    });
}
