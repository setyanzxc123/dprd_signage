<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Agenda Rapat — DPRD Provinsi Sulawesi Tengah</title>
    <meta name="description" content="Informasi agenda rapat DPRD Provinsi Sulawesi Tengah secara real-time. Diperbarui otomatis setiap menit." />
    <meta name="robots" content="noindex, nofollow" />

    <!-- Open Graph (WhatsApp / Sosmed preview) -->
    <meta property="og:title"       content="Agenda Rapat DPRD Sulteng" />
    <meta property="og:description" content="Pantau jadwal rapat DPRD Provinsi Sulawesi Tengah secara langsung dan real-time." />
    <meta property="og:type"        content="website" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="<?= base_url('assets/css/publik.css') ?>" rel="stylesheet" />
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
</head>
<body>

<div id="app">

    <!-- ── Header ── -->
    <header class="pk-header">
        <div class="pk-header-inner">
            <div class="pk-brand">
                <img src="<?= esc($logoUrl) ?>" alt="Logo DPRD" class="pk-logo" />
                <div class="pk-brand-text">
                    <div class="pk-instansi">DPRD Provinsi</div>
                    <div class="pk-provinsi">Sulawesi Tengah</div>
                </div>
            </div>
            <div class="pk-header-right">
                <div class="pk-live-badge">
                    <span class="pk-live-dot"></span>
                    Live
                </div>
            </div>
        </div>
    </header>

    <!-- ── Konten ── -->
    <main class="pk-content">

        <!-- Navigasi Tanggal -->
        <div class="pk-date-nav">
            <button class="pk-nav-btn" @click="prevDay" aria-label="Hari sebelumnya">&#8249;</button>
            <div class="pk-date-center">
                <div class="pk-date-day">{{ dateDay }}</div>
                <div class="pk-date-full">{{ dateFull }}</div>
            </div>
            <button class="pk-nav-btn" @click="nextDay" aria-label="Hari berikutnya">&#8250;</button>
        </div>

        <!-- Ringkasan -->
        <div class="pk-summary">
            <span v-if="loading">Memuat agenda...</span>
            <span v-else-if="jadwal.length === 0">Tidak ada agenda publik</span>
            <span v-else class="pk-count-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                    <path d="M6.5 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-9 3a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm3 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                </svg>
                {{ jadwal.length }} Agenda Rapat
            </span>
            <span class="pk-refresh-time" v-if="lastRefresh">Diperbarui {{ lastRefresh }}</span>
        </div>

        <!-- List Jadwal -->
        <div class="pk-list">

            <!-- Skeleton loader -->
            <div v-if="loading" class="pk-skeleton" v-for="n in 3" :key="'sk-'+n"></div>

            <!-- Empty state -->
            <div v-if="!loading && jadwal.length === 0" class="pk-empty">
                <div class="pk-empty-icon">📅</div>
                <p>Tidak ada agenda rapat yang bersifat terbuka untuk publik pada tanggal ini.</p>
            </div>

            <!-- Card jadwal -->
            <div
                v-for="item in jadwal"
                :key="item.id"
                :class="['pk-card', 'status-' + item.status]"
            >
                <!-- Baris atas: status -->
                <div class="pk-card-top">
                    <span :class="['pk-status-pill', item.status]">
                        <span :class="['pk-status-dot', item.status === 'berlangsung' ? 'pulse' : '']"></span>
                        {{ statusLabel(item.status) }}
                    </span>
                    <span v-if="item.jenis" style="font-size:.7rem;color:var(--pk-muted);">{{ item.jenis }}</span>
                </div>

                <!-- Body -->
                <div class="pk-card-body">
                    <div class="pk-card-title">{{ item.judul }}</div>

                    <div class="pk-card-meta">
                        <!-- Waktu -->
                        <span class="pk-meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                            </svg>
                            {{ item.waktu_mulai }} – {{ item.waktu_selesai }}
                        </span>
                        <!-- Ruangan -->
                        <span class="pk-meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L8 2.207l6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5z"/>
                                <path d="m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293l6-6z"/>
                            </svg>
                            {{ item.ruangan }}
                        </span>
                        <!-- Komisi -->
                        <span class="pk-meta-item" v-if="item.komisi">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/>
                                <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/>
                            </svg>
                            {{ item.komisi }}
                        </span>
                    </div>

                    <div class="pk-card-keterangan" v-if="item.keterangan">{{ item.keterangan }}</div>
                </div>

                <!-- Tombol Live Stream -->
                <a
                    v-if="item.has_stream"
                    :href="item.stream_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="pk-watch-btn"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M6.271 5.055a.5.5 0 0 1 .52.038l3.5 2.5a.5.5 0 0 1 0 .814l-3.5 2.5A.5.5 0 0 1 6 10.5v-5a.5.5 0 0 1 .271-.445z"/>
                    </svg>
                    Tonton Live
                </a>
            </div>

        </div>
    </main>

    <!-- ── Footer ── -->
    <footer class="pk-footer">
        <p>Data diperbarui secara otomatis setiap 60 detik</p>
        <p>DPRD Provinsi Sulawesi Tengah &copy; <?= date('Y') ?></p>
    </footer>

</div>

<script>
    const { createApp, ref, computed, onMounted, onUnmounted } = Vue;

    createApp({
        setup() {
            const today      = new Date();
            const activeDate = ref(new Date(today));
            const jadwal     = ref([]);
            const loading    = ref(true);
            const lastRefresh = ref('');
            const API_URL    = '<?= esc($apiUrl) ?>';

            const dayNames   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            const monthNames = ['Januari','Februari','Maret','April','Mei','Juni',
                                'Juli','Agustus','September','Oktober','November','Desember'];

            const dateDay  = computed(() => dayNames[activeDate.value.getDay()].toUpperCase());
            const dateFull = computed(() =>
                `${activeDate.value.getDate()} ${monthNames[activeDate.value.getMonth()]} ${activeDate.value.getFullYear()}`
            );

            function toYMD(d) {
                return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            }

            function prevDay() {
                const d = new Date(activeDate.value);
                d.setDate(d.getDate() - 1);
                activeDate.value = d;
                loadData();
            }

            function nextDay() {
                const d = new Date(activeDate.value);
                d.setDate(d.getDate() + 1);
                activeDate.value = d;
                loadData();
            }

            function loadData() {
                loading.value = true;
                fetch(`${API_URL}?date=${toYMD(activeDate.value)}`)
                    .then(r => r.json())
                    .then(res => {
                        jadwal.value = res.data ?? [];
                        const now = new Date();
                        lastRefresh.value = `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
                    })
                    .catch(err => console.error('[Publik] Gagal ambil data:', err))
                    .finally(() => { loading.value = false; });
            }

            function statusLabel(status) {
                return {
                    berlangsung: 'Berlangsung',
                    persiapan:   'Persiapan',
                    menunggu:    'Menunggu',
                    selesai:     'Selesai',
                }[status] ?? status;
            }

            let timer = null;
            onMounted(() => {
                loadData();
                timer = setInterval(loadData, 60000);
            });
            onUnmounted(() => clearInterval(timer));

            return { jadwal, loading, lastRefresh, dateDay, dateFull, prevDay, nextDay, statusLabel };
        }
    }).mount('#app');
</script>

</body>
</html>
