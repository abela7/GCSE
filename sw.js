const CACHE_NAME = 'gcse-study-tracker-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/index.php',
  '/dashboard.php',
  '/Status.php',
  '/manifest.json',
  '/assets/css/style.css',
  '/assets/js/main.js',
  '/assets/js/pwa.js',
  '/assets/icons/icon-192x192.png',
  '/assets/icons/icon-512x512.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  'https://code.jquery.com/jquery-3.6.0.min.js'
];

// Install event - cache assets
self.addEventListener('install', event => {
  console.log('[ServiceWorker] Install');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[ServiceWorker] Caching app shell');
        return cache.addAll(ASSETS_TO_CACHE);
      })
      .then(() => {
        console.log('[ServiceWorker] Skip waiting on install');
        return self.skipWaiting();
      })
  );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
  console.log('[ServiceWorker] Activate');
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            console.log('[ServiceWorker] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('[ServiceWorker] Claiming clients');
      return self.clients.claim();
    })
  );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', event => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    console.log('[ServiceWorker] Skipping non-GET request:', event.request.method);
    return;
  }
  
  // Skip cross-origin requests
  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin && 
      !event.request.url.includes('cdn.jsdelivr.net') && 
      !event.request.url.includes('cdnjs.cloudflare.com') && 
      !event.request.url.includes('code.jquery.com')) {
    console.log('[ServiceWorker] Skipping cross-origin fetch:', url.origin);
    return;
  }
  
  console.log('[ServiceWorker] Fetch', event.request.url);
  event.respondWith(
    caches.match(event.request)
      .then(cachedResponse => {
        if (cachedResponse) {
          console.log('[ServiceWorker] Return cached response:', event.request.url);
          return cachedResponse;
        }
        
        console.log('[ServiceWorker] Fetching resource:', event.request.url);
        return fetch(event.request)
          .then(response => {
            // Return the response if it's not valid
            if (!response || response.status !== 200) {
              console.log('[ServiceWorker] Invalid response:', response?.status);
              return response;
            }
            
            // Clone the response
            let responseToCache = response.clone();
            
            // Don't cache API responses, only static assets
            if (!event.request.url.includes('/api/') && 
                (!event.request.url.includes('.php') || 
                event.request.url.endsWith('index.php') ||
                event.request.url.endsWith('dashboard.php') ||
                event.request.url.endsWith('Status.php'))) {
                  
              console.log('[ServiceWorker] Caching new resource:', event.request.url);
              caches.open(CACHE_NAME)
                .then(cache => {
                  cache.put(event.request, responseToCache);
                });
            }
            
            return response;
          })
          .catch(err => {
            console.log('[ServiceWorker] Fetch failed:', err);
            // Return offline page for HTML requests
            if (event.request.headers.get('accept').includes('text/html')) {
              return caches.match('/offline.html');
            }
          });
      })
  );
});

// Console output for debugging
console.log('[ServiceWorker] Script loaded!');
