// PWA Installation Script

console.log("PWA script loaded");

// Register service worker if browser supports it
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    console.log("Attempting to register service worker...");
    navigator.serviceWorker.register('/service-worker.js')
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
const installPrompt = document.querySelector('#installPrompt');
const installButton = document.querySelector('#installButton');

// Function to show install prompt
function showInstallPrompt() {
  if (installPrompt && installButton) {
    console.log('Showing install prompt');
    installPrompt.classList.remove('d-none');
    installPrompt.classList.add('show');
  } else {
    console.warn('Install prompt elements not found in the DOM');
  }
}

// Function to hide install prompt
function hideInstallPrompt() {
  if (installPrompt) {
    console.log('Hiding install prompt');
    installPrompt.classList.remove('show');
    installPrompt.classList.add('d-none');
  }
}

// Listen for 'beforeinstallprompt' event
window.addEventListener('beforeinstallprompt', (e) => {
  console.log('beforeinstallprompt event fired');
  // Prevent Chrome 67 and earlier from automatically showing the prompt
  e.preventDefault();
  // Stash the event so it can be triggered later
  deferredPrompt = e;
  // Show the install button
  showInstallPrompt();
});

// Add click event listener only if the button exists
if (installButton) {
  installButton.addEventListener('click', async () => {
    console.log('Install button clicked');
    if (deferredPrompt) {
      try {
        // Show the install prompt
        deferredPrompt.prompt();
        // Wait for the user to respond to the prompt
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`User response to the install prompt: ${outcome}`);
        // We no longer need the prompt. Clear it up
        deferredPrompt = null;
        // Hide the install button
        hideInstallPrompt();
      } catch (error) {
        console.error('Error during installation:', error);
      }
    } else {
      console.warn('No deferred prompt available');
    }
  });
}

// Handle successful installation
window.addEventListener('appinstalled', (event) => {
  console.log('PWA was installed', event);
  // Hide the install prompt
  hideInstallPrompt();
  // Clear the deferredPrompt
  deferredPrompt = null;
});

// Function to close the install prompt
function closeInstallPrompt() {
  console.log('Closing install prompt');
  hideInstallPrompt();
}

// Check if the app is running in standalone mode (installed)
if (window.matchMedia('(display-mode: standalone)').matches) {
  console.log('App is running in standalone mode');
  hideInstallPrompt();
}

// Make closeInstallPrompt function globally accessible
window.closeInstallPrompt = closeInstallPrompt;

console.log("PWA script initialization complete");
