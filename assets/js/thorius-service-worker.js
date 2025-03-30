// Thorius Service Worker
const CACHE_NAME = 'vortex-thorius-cache-v1';

const ASSETS_TO_CACHE = [
    '/assets/css/thorius-concierge.css',
    '/assets/js/thorius-concierge.js',
    '/assets/js/thorius-voice.js',
    '/assets/js/thorius-agents.js',
    '/assets/images/thorius-logo.png'
];

// Install event
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.addAll(ASSETS_TO_CACHE);
            })
    );
});

// Activate event
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.filter(cacheName => {
                    return cacheName !== CACHE_NAME;
                }).map(cacheName => {
                    return caches.delete(cacheName);
                })
            );
        })
    );
});

// Fetch event
self.addEventListener('fetch', event => {
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached asset if available
                if (response) {
                    return response;
                }
                
                // Clone the request
                const fetchRequest = event.request.clone();
                
                // For API requests, use network-first strategy
                if (fetchRequest.url.includes('wp-admin/admin-ajax.php')) {
                    return fetch(fetchRequest)
                        .catch(() => {
                            // Return offline message for API requests
                            return new Response(
                                JSON.stringify({
                                    success: false,
                                    data: {
                                        message: "You appear to be offline. Please check your connection."
                                    }
                                }),
                                { headers: { 'Content-Type': 'application/json' } }
                            );
                        });
                }
                
                // For other assets, use network then cache strategy
                return fetch(fetchRequest).then(response => {
                    // Check if valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    
                    // Clone the response
                    const responseToCache = response.clone();
                    
                    caches.open(CACHE_NAME)
                        .then(cache => {
                            cache.put(event.request, responseToCache);
                        });
                    
                    return response;
                });
            })
    );
}); 