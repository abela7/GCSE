const CACHE_NAME = 'just-do-it-v2';
const ASSETS_TO_CACHE = [
    '/',
    '/index.php',
    '/manifest.json',
    '/offline.html',
    '/assets/js/pwa.js',
    '/assets/js/main.js',
    '/assets/css/main.css',
    '/assets/css/responsive.css',
    '/assets/favicon/android-chrome-192x192.png',
    '/assets/favicon/android-chrome-512x512.png',
    '/assets/favicon/apple-touch-icon.png',
    '/assets/favicon/favicon-16x16.png',
    '/assets/favicon/favicon-32x32.png',
    '/assets/favicon/favicon.ico',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'https://code.jquery.com/jquery-3.6.0.min.js'
];

// Install Service Worker
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[ServiceWorker] Caching app shell');
                return cache.addAll(ASSETS_TO_CACHE).then(() => {
                    console.log('[ServiceWorker] All assets cached');
                });
            })
            .catch((error) => {
                console.error('[ServiceWorker] Cache addAll error:', error);
            })
    );
    // Force activation
    self.skipWaiting();
});

// Activate Service Worker
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[ServiceWorker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('[ServiceWorker] Claiming clients...');
            return self.clients.claim();
        })
    );
});

// Helper function to determine if a resource should be cached
function shouldCache(url) {
    // Cache static assets
    if (url.match(/\.(js|css|png|jpg|jpeg|gif|ico|json|html)$/)) {
        return true;
    }
    // Cache specific PHP files
    if (url.match(/\/(index|status)\.php$/)) {
        return true;
    }
    return false;
}

// Fetch Event
self.addEventListener('fetch', (event) => {
    console.log('[ServiceWorker] Fetch:', event.request.url);
    
    // Handle non-GET requests
    if (event.request.method !== 'GET') {
        console.log('[ServiceWorker] Non-GET request:', event.request.method);
        return;
    }

    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        console.log('[ServiceWorker] Skipping cross-origin request:', event.request.url);
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                if (response) {
                    console.log('[ServiceWorker] Found in cache:', event.request.url);
                    return response;
                }

                console.log('[ServiceWorker] Network request:', event.request.url);
                return fetch(event.request)
                    .then((response) => {
                        // Check if we received a valid response
                        if (!response || response.status !== 200) {
                            console.log('[ServiceWorker] Invalid response:', response?.status);
                            return response;
                        }

                        // Only cache valid resources
                        if (shouldCache(event.request.url)) {
                            console.log('[ServiceWorker] Caching new resource:', event.request.url);
                            const responseToCache = response.clone();
                            caches.open(CACHE_NAME)
                                .then((cache) => {
                                    cache.put(event.request, responseToCache);
                                });
                        }

                        return response;
                    })
                    .catch((error) => {
                        console.error('[ServiceWorker] Fetch error:', error);
                        // Return offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            console.log('[ServiceWorker] Returning offline page');
                            return caches.match('/offline.html');
                        }
                        throw error;
                    });
            })
    );
}); 