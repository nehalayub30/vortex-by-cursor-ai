/**
 * Register service worker for notifications
 */
function registerVortexServiceWorker() {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        navigator.serviceWorker.register('/wp-content/plugins/vortex-ai-marketplace/public/js/vortex-notification-worker.js')
            .then(function(registration) {
                console.log('Vortex service worker registered:', registration);
                
                // Check if already subscribed
                return registration.pushManager.getSubscription()
                    .then(function(subscription) {
                        if (subscription) {
                            return subscription;
                        }
                        
                        // Subscribe if notifications allowed
                        if (Notification.permission === 'granted') {
                            return subscribeUserToPush(registration);
                        }
                    });
            })
            .then(function(subscription) {
                if (subscription) {
                    // Send subscription to server
                    saveSubscription(subscription);
                }
            })
            .catch(function(error) {
                console.error('Service worker registration failed:', error);
            });
    }
}

/**
 * Subscribe user to push notifications
 */
function subscribeUserToPush(registration) {
    const publicKey = vortex_notifications.public_key;
    
    // Convert public key to Uint8Array
    const applicationServerKey = urlBase64ToUint8Array(publicKey);
    
    return registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey
    });
}

/**
 * Save subscription on server
 */
function saveSubscription(subscription) {
    const endpoint = subscription.endpoint;
    const key = subscription.getKey('p256dh');
    const auth = subscription.getKey('auth');
    
    const subscriptionData = {
        endpoint: endpoint,
        keys: {
            p256dh: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : '',
            auth: auth ? btoa(String.fromCharCode.apply(null, new Uint8Array(auth))) : ''
        }
    };
    
    // Send to server
    jQuery.ajax({
        url: vortex_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'vortex_save_push_subscription',
            subscription: JSON.stringify(subscriptionData),
            nonce: vortex_notifications.subscription_nonce
        }
    });
}

/**
 * Convert base64 to Uint8Array
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');
    
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    
    return outputArray;
}

// Initialize when document is ready
jQuery(document).ready(function() {
    // Check if user has enabled notifications
    if (vortex_notifications.enabled === '1' && Notification.permission === 'granted') {
        registerVortexServiceWorker();
    }
}); 