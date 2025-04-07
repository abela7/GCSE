// Service worker for notification handling

const CACHE_NAME = 'gcse-cache-v1';

// Install event - cache essential files
self.addEventListener('install', event => {
  console.log('Service Worker installing...');
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll([
        '/',
        '/assets/favicon/favicon-32x32.png',
        // Add other essential assets here
      ]);
    })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker activating...');
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.filter(name => name !== CACHE_NAME)
          .map(name => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// Listen for notification clicks
self.addEventListener('notificationclick', event => {
  console.log('Notification clicked:', event.notification.tag);
  event.notification.close();

  // Handle notification click actions
  if (event.action === 'view') {
    console.log('User clicked View action');
  } else if (event.action === 'close') {
    console.log('User clicked Close action');
    return;
  }

  // Focus on existing window or open new one
  event.waitUntil(
    clients.matchAll({type: 'window', includeUncontrolled: true})
      .then(clientList => {
        const urlToOpen = event.notification.data && 
                          event.notification.data.url ? 
                          event.notification.data.url : '/';
                          
        for (let client of clientList) {
          if (client.url === urlToOpen && 'focus' in client) {
            return client.focus();
          }
        }
        
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
  );
});

// Handle push notifications
self.addEventListener('push', event => {
  console.log('Push received:', event);
  
  let notificationData = {
    title: 'New Notification',
    options: {
      body: 'Something new happened!',
      icon: '/assets/favicon/favicon-32x32.png',
      badge: '/assets/favicon/favicon-32x32.png'
    }
  };
  
  if (event.data) {
    try {
      notificationData = event.data.json();
    } catch (e) {
      console.error('Push event data not JSON:', e);
    }
  }
  
  event.waitUntil(
    self.registration.showNotification(notificationData.title, notificationData.options)
  );
});