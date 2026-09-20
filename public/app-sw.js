// Service worker push notification untuk aplikasi agenda (PWA).
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        payload = { title: 'Agenda DPRD', message: event.data ? event.data.text() : '' };
    }

    const options = {
        body: payload.message || '',
        icon: '/assets/images/logo_dprd.png',
        badge: '/assets/images/logo_dprd.png',
        data: { url: payload.url || '/agenda' },
    };

    event.waitUntil(self.registration.showNotification(payload.title || 'Agenda DPRD', options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = new URL(event.notification.data?.url || '/agenda', self.location.origin).href;

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
        for (const client of clientList) {
            if (client.url === target && 'focus' in client) {
                return client.focus();
            }
        }

        return self.clients.openWindow(target);
    }));
});
