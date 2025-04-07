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
  // Add Bootstrap and other CDN resources
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
  'https://use.fontawesome.com/releases/v6.1.1/css/all.css'
];

// Install event - cache assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache opened');
        return cache.addAll(ASSETS_TO_CACHE);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', event => {
  // Skip cross-origin requests
  if (event.request.url.startsWith(self.location.origin) || 
      event.request.url.includes('cdn.jsdelivr.net') || 
      event.request.url.includes('use.fontawesome.com')) {
    
    event.respondWith(
      caches.match(event.request)
        .then(cachedResponse => {
          if (cachedResponse) {
            return cachedResponse;
          }
          
          return fetch(event.request)
            .then(response => {
              // Return the response if it's not valid or not a GET request
              if (!response || response.status !== 200 || event.request.method !== 'GET') {
                return response;
              }
              
              // Clone the response
              let responseToCache = response.clone();
              
              // Don't cache PHP API responses, only static assets
              if (!event.request.url.includes('.php') || 
                  event.request.url.endsWith('index.php') ||
                  event.request.url.endsWith('dashboard.php') ||
                  event.request.url.endsWith('Status.php')) {
                caches.open(CACHE_NAME)
                  .then(cache => {
                    cache.put(event.request, responseToCache);
                  });
              }
              
              return response;
            })
            .catch(() => {
              // If fetch fails (offline), try to return cached page for HTML requests
              if (event.request.headers.get('accept').includes('text/html')) {
                return caches.match('/offline.html');
              }
            });
        })
    );
  }
});
