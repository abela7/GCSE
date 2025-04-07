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
const installButton = document.getElementById('installApp');
const installPrompt = document.getElementById('pwa-install-prompt');

// Listen for 'beforeinstallprompt' event
window.addEventListener('beforeinstallprompt', (e) => {
  console.log('beforeinstallprompt event fired');
  // Prevent the mini-infobar from appearing on mobile
  e.preventDefault();
  
  // Stash the event so it can be triggered later
  deferredPrompt = e;
  
  // Show the install button or prompt
  if (installPrompt) {
    console.log('Showing install prompt');
    installPrompt.classList.add('show');
  }
  
  if (installButton) {
    installButton.style.display = 'block';
    
    // Add click event to install button
    installButton.addEventListener('click', installPWA);
  }
});

// Function to handle app installation
function installPWA() {
  console.log('Install button clicked');
  if (!deferredPrompt) {
    console.log('No deferred prompt available');
    return;
  }
  
  // Show the install prompt
  deferredPrompt.prompt();
  
  // Wait for the user to respond to the prompt
  deferredPrompt.userChoice.then((choiceResult) => {
    if (choiceResult.outcome === 'accepted') {
      console.log('User accepted the install prompt');
      if (installPrompt) {
        installPrompt.classList.remove('show');
      }
    } else {
      console.log('User dismissed the install prompt');
    }
    // Clear the deferredPrompt variable
    deferredPrompt = null;
  });
}

// Listen for successful installation
window.addEventListener('appinstalled', (evt) => {
  console.log('GCSE Study Tracker has been installed');
  
  // Hide install prompt after successful installation
  if (installPrompt) {
    installPrompt.classList.remove('show');
  }
  
  if (installButton) {
    installButton.style.display = 'none';
  }
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
