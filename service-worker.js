const CACHE_NAME = 'just-do-it-v1';
const ASSETS_TO_CACHE = [
    '/',
    '/index.php',
    '/pages/tasks.php',
    '/pages/status.php',
    '/assets/js/main.js',
    '/assets/css/style.css',
    '/assets/favicon/android-chrome-192x192.png',
    '/assets/favicon/android-chrome-512x512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js',
    'https://code.jquery.com/jquery-3.6.0.min.js'
];

// Install Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                return cache.addAll(ASSETS_TO_CACHE);
            })
    );
});

// Activate Service Worker
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Fetch Event
self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Return cached version or fetch new version
                return response || fetch(event.request)
                    .then((response) => {
                        // Cache new responses for future offline use
                        if (response.status === 200) {
                            const responseClone = response.clone();
                            caches.open(CACHE_NAME)
                                .then((cache) => {
                                    cache.put(event.request, responseClone);
                                });
                        }
                        return response;
                    });
            })
            .catch(() => {
                // Return offline fallback if both cache and network fail
                if (event.request.mode === 'navigate') {
                    return caches.match('/offline.html');
                }
            })
    );
}); 