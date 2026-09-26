// Push notification browser untuk panel admin.
// Mengandalkan service worker /app-sw.js yang sama dengan portal anggota.
(function () {
    'use strict';

    var config = window.AdminPushConfig || {};
    var button = document.getElementById('btn_aktifkan_push');

    function hideButton() {
        if (button) {
            // Inline style agar tidak kalah oleh rule .inline-flex pada urutan CSS hasil build.
            button.style.display = 'none';
        }
    }

    async function init() {
        if (!button || !config.publicKey || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            return;
        }

        try {
            var registration = await navigator.serviceWorker.register('/app-sw.js');
            var subscription = await registration.pushManager.getSubscription();
            if (subscription && Notification.permission === 'granted') {
                hideButton();
            }
        } catch (error) {
            console.warn('Inisialisasi push admin gagal:', error);
        }
    }

    async function activate() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            window.alert('Browser ini tidak mendukung push notification.');
            return;
        }

        var tampilanAwal = button.innerHTML;
        button.innerHTML = 'Memproses...';

        try {
            var registration = await navigator.serviceWorker.register('/app-sw.js');
            var permission = Notification.permission === 'granted'
                ? 'granted'
                : await Notification.requestPermission();
            if (permission !== 'granted') {
                window.alert('Izin notifikasi ditolak.');
                button.innerHTML = tampilanAwal;
                return;
            }

            var subscription = await registration.pushManager.getSubscription();
            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: config.publicKey
                });
            }

            var headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
            if (config.csrfHeader && config.csrfToken) {
                headers[config.csrfHeader] = config.csrfToken;
            }

            var response = await fetch(config.subscribeUrl, {
                method: 'POST',
                headers: headers,
                credentials: 'same-origin',
                body: JSON.stringify(subscription)
            });

            if (response.ok) {
                hideButton();
                window.alert('SUKSES: Panel admin kini siap menerima notifikasi browser!');
            } else {
                var result = await response.json().catch(function () { return {}; });
                window.alert(result.message || 'GAGAL: Pastikan sesi admin masih valid.');
                button.innerHTML = tampilanAwal;
            }
        } catch (error) {
            console.error('Aktivasi push admin gagal:', error);
            window.alert('Terjadi kesalahan jaringan atau VAPID Key tidak valid.');
            button.innerHTML = tampilanAwal;
        }
    }

    if (button) {
        button.addEventListener('click', activate);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // BFCache: saat kembali dari halaman lain lewat cache, init dijalankan ulang
    // supaya status tombol tetap sesuai kondisi langganan terbaru.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            init();
        }
    });
})();
