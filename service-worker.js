const CACHE_NAME = 'just-do-it-v5';
const ASSETS_TO_CACHE = [
    './',
    './index.php',
    './manifest.json',
    './offline.html',
    './assets/js/pwa.js',
    './assets/js/main.js',
    './assets/css/main.css',
    './assets/css/responsive.css',
    './assets/favicon/android-chrome-192x192.png',
    './assets/favicon/android-chrome-512x512.png',
    './assets/favicon/apple-touch-icon.png',
    './assets/favicon/favicon-16x16.png',
    './assets/favicon/favicon-32x32.png',
    './assets/favicon/favicon.ico',
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
    try {
        const parsedUrl = new URL(url);
        
        // Don't cache cross-origin requests except for our CDN resources
        if (!parsedUrl.href.startsWith(self.location.origin) && 
            !parsedUrl.href.includes('cdn.jsdelivr.net') && 
            !parsedUrl.href.includes('cdnjs.cloudflare.com') &&
            !parsedUrl.href.includes('code.jquery.com')) {
            return false;
        }
        
        // Always cache static assets
        if (parsedUrl.pathname.match(/\.(js|css|png|jpg|jpeg|gif|ico|json|html)$/)) {
            return true;
        }
        
        // Cache the main entry points
        const path = parsedUrl.pathname.replace(/^\/+/, '');
        if (path === '' || 
            path === 'index.php' || 
            path.endsWith('/index.php')) {
            return true;
        }
        
        return false;
    } catch (error) {
        console.error('[ServiceWorker] Error in shouldCache:', error);
        return false;
    }
}

// Network-first strategy for dynamic content
async function networkFirst(request) {
    try {
        console.log('[ServiceWorker] Trying network first for:', request.url);
        const networkResponse = await fetch(request);
        if (networkResponse && networkResponse.status === 200) {
            // Cache successful responses
            if (shouldCache(request.url)) {
                const cache = await caches.open(CACHE_NAME);
                await cache.put(request, networkResponse.clone());
                console.log('[ServiceWorker] Cached network response for:', request.url);
            }
            return networkResponse;
        }
        throw new Error('Network response was not ok');
    } catch (error) {
        console.log('[ServiceWorker] Network fetch failed, falling back to cache for:', request.url);
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            console.log('[ServiceWorker] Returning cached response for:', request.url);
            return cachedResponse;
        }
        // If it's a navigation request, return the offline page
        if (request.mode === 'navigate') {
            console.log('[ServiceWorker] Returning offline page for:', request.url);
            const cache = await caches.open(CACHE_NAME);
            return cache.match('./offline.html');
        }
        throw error;
    }
}

// Cache-first strategy for static assets
async function cacheFirst(request) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            console.log('[ServiceWorker] Returning cached response for:', request.url);
            return cachedResponse;
        }
        console.log('[ServiceWorker] No cache found, fetching from network:', request.url);
        const networkResponse = await fetch(request);
        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(CACHE_NAME);
            await cache.put(request, networkResponse.clone());
            console.log('[ServiceWorker] Cached network response for:', request.url);
        }
        return networkResponse;
    } catch (error) {
        console.error('[ServiceWorker] Cache first strategy failed for:', request.url, error);
        throw error;
    }
}

// Fetch Event
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Handle the fetch event for all requests
    event.respondWith(
        // Use cache-first for static assets, network-first for everything else
        shouldCache(event.request.url) ?
            cacheFirst(event.request) :
            networkFirst(event.request)
    );
}); 