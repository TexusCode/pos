const LEGACY_CACHE_PREFIXES = ['pos-static-', 'pos-runtime-'];
const CLEANUP_FLAG_KEY = 'pos_offline_cleanup_v1';

const cleanupLegacyOfflineArtifacts = async () => {
    try {
        if (window.localStorage?.getItem(CLEANUP_FLAG_KEY) === '1') {
            return;
        }
    } catch {
        // Ignore storage access issues and continue cleanup.
    }

    try {
        if ('serviceWorker' in navigator) {
            const registrations = await navigator.serviceWorker.getRegistrations();
            await Promise.all(registrations.map((registration) => registration.unregister()));
        }

        if ('caches' in window) {
            const cacheNames = await caches.keys();
            const legacyCacheNames = cacheNames.filter((name) =>
                LEGACY_CACHE_PREFIXES.some((prefix) => name.startsWith(prefix)),
            );

            await Promise.all(legacyCacheNames.map((name) => caches.delete(name)));
        }
    } catch (error) {
        console.error('Legacy offline cleanup failed:', error);
    } finally {
        try {
            window.localStorage?.setItem(CLEANUP_FLAG_KEY, '1');
        } catch {
            // Ignore storage access issues.
        }
    }
};

window.addEventListener('load', cleanupLegacyOfflineArtifacts, { once: true });
