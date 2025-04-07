// PWA Installation Script

console.log("PWA script loaded");

// Register service worker if browser supports it
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    console.log("Attempting to register service worker...");
    navigator.serviceWorker.register('/sw.js')
      .then(registration => {
        console.log('ServiceWorker registration successful with scope: ', registration.scope);
      })
      .catch(error => {
        console.error('ServiceWorker registration failed: ', error);
      });
  });
}

// Variables to store install prompt event
let deferredPrompt;
const installPrompt = document.getElementById('installPrompt');
const installButton = document.getElementById('installButton');

// Listen for 'beforeinstallprompt' event
window.addEventListener('beforeinstallprompt', (e) => {
  // Prevent Chrome 67 and earlier from automatically showing the prompt
  e.preventDefault();
  // Stash the event so it can be triggered later
  deferredPrompt = e;
  // Show the install button
  installPrompt.classList.remove('d-none');
  installPrompt.classList.add('show');
});

installButton.addEventListener('click', async () => {
  if (deferredPrompt) {
    // Show the install prompt
    deferredPrompt.prompt();
    // Wait for the user to respond to the prompt
    const { outcome } = await deferredPrompt.userChoice;
    // We no longer need the prompt. Clear it up
    deferredPrompt = null;
    // Hide the install button
    installPrompt.classList.remove('show');
    installPrompt.classList.add('d-none');
  }
});

// Handle successful installation
window.addEventListener('appinstalled', () => {
  // Hide the install prompt
  installPrompt.classList.remove('show');
  installPrompt.classList.add('d-none');
  // Clear the deferredPrompt
  deferredPrompt = null;
  // Log the installation (you can add analytics here)
  console.log('PWA was installed');
});

// Function to close the install prompt
function closeInstallPrompt() {
  console.log('Closing install prompt');
  if (installPrompt) {
    installPrompt.classList.remove('show');
  }
}

// Check if the app is running in standalone mode (installed)
if (window.matchMedia('(display-mode: standalone)').matches) {
  console.log('App is running in standalone mode');
  
  // Hide install elements when app is already installed
  if (installPrompt) {
    installPrompt.classList.remove('show');
  }
  
  if (installButton) {
    installButton.style.display = 'none';
  }
}

console.log("PWA script initialization complete");

// Make closeInstallPrompt function globally accessible
window.closeInstallPrompt = closeInstallPrompt;
