<script {csp-script-nonce}>
    const csrfHeader = '<?= csrf_header() ?>';
    const csrfToken  = '<?= csrf_hash() ?>';

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/app-sw.js').then(async (registration) => {
            const notifButtons = [...document.querySelectorAll('.js-aktifkan-notif')];

            if (notifButtons.length) {
                const existingSubscription = await registration.pushManager.getSubscription();
                if (Notification.permission === 'granted' && existingSubscription) {
                    notifButtons.forEach((btn) => { btn.style.display = 'none'; });
                }

                notifButtons.forEach((btn) => btn.addEventListener('click', async () => {
                    const tampilanAwal = btn.innerHTML;
                    btn.innerHTML = 'Memproses...';

                    try {
                        const permission = await Notification.requestPermission();
                        if (permission !== 'granted') {
                            alert('Izin notifikasi ditolak.');
                            btn.innerHTML = tampilanAwal;
                            return;
                        }

                        let subscription = await existingSubscription;
                        if (!subscription) {
                            subscription = await registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: '<?= env("VAPID_PUBLIC_KEY") ?>'
                            });
                        }

                        const response = await fetch('/member/simpan-notif-hp', {
                            method: 'POST',
                            body: JSON.stringify(subscription),
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                [csrfHeader]: csrfToken
                            },
                            credentials: 'same-origin'
                        });

                        if (response.ok) {
                            notifButtons.forEach((b) => { b.style.display = 'none'; });
                            alert('SUKSES: Perangkat Anda kini siap menerima notifikasi jadwal!');
                        } else {
                            alert('GAGAL: Pastikan Anda sudah Login sebagai anggota.');
                            btn.innerHTML = tampilanAwal;
                        }

                    } catch (error) {
                        console.error('Push Error:', error);
                        alert('Terjadi kesalahan jaringan atau VAPID Key tidak valid.');
                        btn.innerHTML = tampilanAwal;
                    }
                }));
            }
        }).catch(err => console.log('SW Error:', err));
    }

    const ctaBanner = document.getElementById('cta-banner');
    if (ctaBanner) {
        const tutupCta = ctaBanner.querySelector('.js-tutup-cta');
        if (sessionStorage.getItem('agendaCtaDitutup') === '1') {
            ctaBanner.classList.add('hidden');
        }
        tutupCta?.addEventListener('click', () => {
            sessionStorage.setItem('agendaCtaDitutup', '1');
            ctaBanner.classList.add('hidden');
        });
    }

    let penangkapInstall = null;
    const installButtons = [...document.querySelectorAll('.js-install-pwa')];

    if (installButtons.length) {
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            penangkapInstall = e;
        });

        installButtons.forEach((btn) => btn.addEventListener('click', async () => {
            if (penangkapInstall !== null) {
                penangkapInstall.prompt();
                const { outcome } = await penangkapInstall.userChoice;
                penangkapInstall = null;
            } else {
                alert('Untuk menginstall aplikasi ini:\n\nDi Android (Chrome):\nKlik ikon titik tiga di pojok kanan atas browser, lalu pilih "Tambahkan ke Layar Utama" atau "Install Aplikasi".\n\nDi iPhone (Safari):\nTekan tombol Bagikan (Share) di bawah layar, lalu pilih "Add to Home Screen".');
            }
        }));
    }
</script>
