// PWA Installation Script

console.log("PWA script loaded");

// Register service worker if browser supports it
if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      console.log("Attempting to register service worker...");
      const registration = await navigator.serviceWorker.register('/service-worker.js');
      console.log('ServiceWorker registration successful with scope: ', registration.scope);
      
      // Check if already installed
      const isInstalled = window.matchMedia('(display-mode: standalone)').matches;
      if (isInstalled) {
        console.log('PWA is already installed');
      } else {
        console.log('PWA is not installed yet');
      }
    } catch (error) {
      console.error('ServiceWorker registration failed: ', error);
    }
  });
} else {
  console.warn('Service workers are not supported');
}

// Variables to store install prompt event
let deferredPrompt;

// Function to show install prompt
function showInstallPrompt() {
  const installPrompt = document.querySelector('#installPrompt');
  const installButton = document.querySelector('#installButton');
  
  if (installPrompt && installButton) {
    console.log('Showing install prompt');
    installPrompt.classList.remove('d-none');
    installPrompt.classList.add('show');
    
    // Make sure the prompt is visible
    installPrompt.style.display = 'block';
    installPrompt.style.opacity = '1';
  } else {
    console.warn('Install prompt elements not found:', {
      installPrompt: !!installPrompt,
      installButton: !!installButton
    });
  }
}

// Function to hide install prompt
function hideInstallPrompt() {
  const installPrompt = document.querySelector('#installPrompt');
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
  
  // Show the prompt automatically after 2 seconds
  setTimeout(() => {
    if (deferredPrompt) {
      console.log('Auto-showing install prompt');
      deferredPrompt.prompt();
    }
  }, 2000);
});

// Add click event handler for install button
document.addEventListener('DOMContentLoaded', () => {
  const installButton = document.querySelector('#installButton');
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
        // Try to show the native install prompt
        window.location.href = 'https://support.google.com/chrome/answer/9658361';
      }
    });
  } else {
    console.warn('Install button not found in DOM');
  }
});

// Handle successful installation
window.addEventListener('appinstalled', (event) => {
  console.log('PWA was installed', event);
  // Hide the install prompt
  hideInstallPrompt();
  // Clear the deferredPrompt
  deferredPrompt = null;
  // Show a success message
  alert('Thank you for installing Just Do It! You can now access it from your home screen.');
});

// Function to close the install prompt
function closeInstallPrompt() {
  console.log('Closing install prompt');
  hideInstallPrompt();
}

// Check if running in standalone mode
if (window.matchMedia('(display-mode: standalone)').matches) {
  console.log('App is running in standalone mode');
  hideInstallPrompt();
} else {
  console.log('App is running in browser mode');
}

// Make closeInstallPrompt function globally accessible
window.closeInstallPrompt = closeInstallPrompt;

console.log("PWA script initialization complete");
