// Debug function to log all details about a request
function debugRequest(prefix, request) {
    console.log(`[DEBUG] ${prefix} Details:`, {
        url: request.url,
        method: request.method,
        mode: request.mode,
        credentials: request.credentials,
        destination: request.destination,
        referrer: request.referrer,
        headers: Array.from(request.headers.entries()),
        cache: request.cache
    });
}

// Debug function to log response details
function debugResponse(prefix, response) {
    if (!response) {
        console.log(`[DEBUG] ${prefix}: Response is null or undefined`);
        return;
    }
    console.log(`[DEBUG] ${prefix} Details:`, {
        url: response.url,
        status: response.status,
        statusText: response.statusText,
        type: response.type,
        headers: Array.from(response.headers.entries()),
        redirected: response.redirected
    });
}

const CACHE_NAME = 'just-do-it-v8';
const ASSETS_TO_CACHE = [
    '.',
    'pages/dashboard.php',
    'manifest.json',
    'offline.html',
    'assets/js/pwa.js',
    'assets/js/main.js',
    'assets/css/main.css',
    'assets/css/responsive.css',
    'assets/favicon/android-chrome-192x192.png',
    'assets/favicon/android-chrome-512x512.png',
    'assets/favicon/apple-touch-icon.png',
    'assets/favicon/favicon-16x16.png',
    'assets/favicon/favicon-32x32.png',
    'assets/favicon/favicon.ico',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'https://code.jquery.com/jquery-3.6.0.min.js'
];

// Install Service Worker
self.addEventListener('install', (event) => {
    console.log('[DEBUG] Service Worker installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[DEBUG] Caching app shell');
                const cachePromises = ASSETS_TO_CACHE.map(url => {
                    const resourceUrl = url.startsWith('http') ? url : new URL(url, self.location.origin).href;
                    console.log('[DEBUG] Attempting to cache:', resourceUrl);
                    return fetch(resourceUrl)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`Failed to fetch ${resourceUrl}: ${response.status} ${response.statusText}`);
                            }
                            return cache.put(resourceUrl, response);
                        })
                        .then(() => console.log('[DEBUG] Successfully cached:', resourceUrl))
                        .catch(error => console.error('[DEBUG] Failed to cache:', resourceUrl, error));
                });
                return Promise.all(cachePromises);
            })
            .then(() => {
                console.log('[DEBUG] All assets cached successfully');
                return self.skipWaiting();
            })
    );
});

// Activate Service Worker
self.addEventListener('activate', (event) => {
    console.log('[DEBUG] Service Worker activating...');
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                console.log('[DEBUG] Existing caches:', cacheNames);
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('[DEBUG] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[DEBUG] Claiming clients...');
                return self.clients.claim();
            })
            .then(() => {
                console.log('[DEBUG] Service Worker activated and claimed clients');
            })
    );
});

// Handle root URL redirect
function handleRootRequest(request) {
    // If it's the root URL, redirect to dashboard
    const url = new URL(request.url);
    if (url.pathname === '/' || url.pathname === '/index.php') {
        return Response.redirect(`${url.origin}/pages/dashboard.php`, 302);
    }
    return null;
}

// Fetch Event
self.addEventListener('fetch', (event) => {
    // Check for root URL redirect
    const redirectResponse = handleRootRequest(event.request);
    if (redirectResponse) {
        event.respondWith(redirectResponse);
        return;
    }

    // Handle navigation requests (HTML pages)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    return response;
                })
                .catch(error => {
                    return caches.match('offline.html');
                })
        );
        return;
    }

    // Handle all other requests
    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }

                return fetch(event.request)
                    .then(response => {
                        if (!response || response.status !== 200) {
                            return response;
                        }

                        const responseToCache = response.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    })
                    .catch(error => {
                        if (event.request.mode === 'navigate') {
                            return caches.match('offline.html');
                        }
                        throw error;
                    });
            })
    );
}); 