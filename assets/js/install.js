let deferredPrompt;
const installButton = document.createElement('button');
installButton.classList.add('btn', 'btn-install');
installButton.style.display = 'none';
installButton.innerHTML = '<i class="fas fa-download me-2"></i>Install App';

// Detect if the app can be installed
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent Chrome 67 and earlier from automatically showing the prompt
    e.preventDefault();
    // Stash the event so it can be triggered later
    deferredPrompt = e;
    // Show the install button
    installButton.style.display = 'block';
    
    // Show install banner
    const banner = document.createElement('div');
    banner.classList.add('install-banner');
    banner.innerHTML = `
        <div class="d-flex align-items-center justify-content-between p-3">
            <div class="d-flex align-items-center">
                <img src="/favicon/android-chrome-192x192.png" alt="Do It!" style="width: 32px; height: 32px; margin-right: 12px;">
                <div>
                    <strong>Install Do It!</strong>
                    <p class="mb-0 text-muted">Get quick access to your daily tasks</p>
                </div>
            </div>
            <button class="btn btn-primary btn-install-banner">Install</button>
        </div>
    `;
    document.body.appendChild(banner);
    
    // Handle banner install button click
    banner.querySelector('.btn-install-banner').addEventListener('click', handleInstall);
});

// Installation must be done by a user gesture
async function handleInstall() {
    if (deferredPrompt) {
        // Show the prompt
        deferredPrompt.prompt();
        
        // Wait for the user to respond to the prompt
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`User response to the install prompt: ${outcome}`);
        
        // Clear the deferredPrompt variable
        deferredPrompt = null;
        
        // Hide the install button/banner
        installButton.style.display = 'none';
        document.querySelector('.install-banner')?.remove();
    }
}

// Handle successful installation
window.addEventListener('appinstalled', (evt) => {
    console.log('App was successfully installed');
    // Hide install prompts after successful installation
    installButton.style.display = 'none';
    document.querySelector('.install-banner')?.remove();
}); 