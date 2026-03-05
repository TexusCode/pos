const scopePath = (() => {
    try {
        const pathname = new URL(self.registration.scope).pathname;
        if (pathname === "/" || pathname === "") {
            return "";
        }

        return pathname.endsWith("/") ? pathname.slice(0, -1) : pathname;
    } catch {
        return "";
    }
})();

const withScope = (path) => `${scopePath}${path}`;

const STATIC_CACHE = "pos-static-v4";
const RUNTIME_CACHE = "pos-runtime-v4";
const OFFLINE_URL = withScope("/offline.html");
const BUILD_MANIFEST_CANDIDATES = [
    withScope("/build/manifest.json"),
    withScope("/public/build/manifest.json"),
];

const PRECACHE_URLS = [
    OFFLINE_URL,
    withScope("/manifest.webmanifest"),
    withScope("/favicon.ico"),
];

const buildManifestAssetUrls = async () => {
    const urls = new Set(BUILD_MANIFEST_CANDIDATES);

    const addBuildAsset = (assetFile) => {
        urls.add(withScope(`/build/${assetFile}`));
        urls.add(withScope(`/public/build/${assetFile}`));
    };

    let manifest = null;

    for (const manifestUrl of BUILD_MANIFEST_CANDIDATES) {
        try {
            const response = await fetch(manifestUrl, { cache: "no-store" });
            if (!response.ok) {
                continue;
            }

            manifest = await response.json();
            break;
        } catch {
            // try next manifest path.
        }
    }

    if (!manifest) {
        return urls;
    }

    try {
        const visitedKeys = new Set();

        const addEntry = (entryKey) => {
            if (visitedKeys.has(entryKey)) {
                return;
            }
            visitedKeys.add(entryKey);

            const entry = manifest[entryKey];
            if (!entry) {
                return;
            }

            if (entry.file) {
                addBuildAsset(entry.file);
            }

            if (Array.isArray(entry.css)) {
                entry.css.forEach(addBuildAsset);
            }

            if (Array.isArray(entry.assets)) {
                entry.assets.forEach(addBuildAsset);
            }

            if (Array.isArray(entry.imports)) {
                entry.imports.forEach(addEntry);
            }
        };

        Object.keys(manifest).forEach(addEntry);
    } catch {
        // ignore manifest read errors and continue with base precache.
    }

    return urls;
};

const precacheRequest = async (cache, url) => {
    try {
        const request = new Request(url, { cache: "reload" });
        const response = await fetch(request);

        if (response.ok) {
            await cache.put(request, response.clone());
        }
    } catch {
        // ignore unreachable resources during install.
    }
};

self.addEventListener("install", (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(STATIC_CACHE);
            const buildUrls = await buildManifestAssetUrls();
            const allUrls = [...new Set([...PRECACHE_URLS, ...buildUrls])];
            await Promise.allSettled(allUrls.map((url) => precacheRequest(cache, url)));
        })(),
    );
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        (async () => {
            const cacheNames = await caches.keys();
            await Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName !== STATIC_CACHE && cacheName !== RUNTIME_CACHE)
                    .map((cacheName) => caches.delete(cacheName)),
            );
            await self.clients.claim();
        })(),
    );
});

self.addEventListener("message", (event) => {
    if (event.data?.type === "SKIP_WAITING") {
        self.skipWaiting();
    }
});

const isAssetRequest = (url) => {
    if (url.pathname.startsWith(withScope("/build/"))) {
        return true;
    }

    if (url.pathname.startsWith(withScope("/public/build/"))) {
        return true;
    }

    return /\.(?:js|css|png|jpe?g|svg|ico|webp|woff2?)$/i.test(url.pathname);
};

self.addEventListener("fetch", (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== "GET" || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === "navigate") {
        event.respondWith(
            (async () => {
                try {
                    const fresh = await fetch(request);
                    const runtimeCache = await caches.open(RUNTIME_CACHE);
                    await runtimeCache.put(request, fresh.clone());
                    return fresh;
                } catch {
                    const cached = await caches.match(request);
                    if (cached) {
                        return cached;
                    }

                    const offlinePage = await caches.match(OFFLINE_URL);
                    if (offlinePage) {
                        return offlinePage;
                    }

                    return new Response("Offline", {
                        status: 503,
                        headers: { "Content-Type": "text/plain; charset=utf-8" },
                    });
                }
            })(),
        );
        return;
    }

    if (isAssetRequest(url)) {
        event.respondWith(
            (async () => {
                const cached = await caches.match(request);
                if (cached) {
                    return cached;
                }

                try {
                    const fresh = await fetch(request);
                    const runtimeCache = await caches.open(RUNTIME_CACHE);
                    await runtimeCache.put(request, fresh.clone());
                    return fresh;
                } catch {
                    return cached || new Response("", { status: 504 });
                }
            })(),
        );
    }
});
