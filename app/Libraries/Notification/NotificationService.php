<?php

namespace App\Libraries\Notification;

use App\Libraries\Notulen\NotulenService;
use App\Libraries\Otp\Providers\BaileysProvider;
use App\Models\SettingModel;
use Config\Otp;

final class NotificationService
{
    public const ALERTS_CACHE_KEY = 'notification_feed_alerts';
    public const ALERTS_CACHE_TTL = 10;
    public const WA_STATUS_CACHE_KEY = 'notification_feed_wa_status';
    public const WA_STATUS_CACHE_TTL = 30;

    private BaileysProvider $baileysProvider;
    private NotulenService $notulenService;
    private Otp $otpConfig;

    public function __construct(
        ?BaileysProvider $baileysProvider = null,
        ?NotulenService $notulenService = null,
        ?Otp $otpConfig = null
    ) {
        $this->otpConfig = $otpConfig ?? config('Otp');
        $this->baileysProvider = $baileysProvider ?? new BaileysProvider(config: $this->otpConfig);
        $this->notulenService = $notulenService ?? new NotulenService();
    }

    /**
     * Mengambil seluruh feed notifikasi terpadu (alerts & ai tasks).
     *
     * @return array<string, mixed>
     */
    public function getFeed(bool $fresh = false): array
    {
        $alerts = $this->getCachedAlerts($fresh);

        $criticalCount = 0;
        $warningCount  = 0;
        $infoCount     = 0;

        foreach ($alerts as $alert) {
            $severity = $alert['severity'] ?? 'info';
            if ($severity === 'critical') {
                $criticalCount++;
            } elseif ($severity === 'warning') {
                $warningCount++;
            } else {
                $infoCount++;
            }
        }

        try {
            $aiSummary = $this->notulenService->getActiveTasksSummary();
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi: gagal mengambil ringkasan antrean AI. {message}', ['message' => $e->getMessage()]);
            $aiSummary = [
                'active_count' => 0,
                'active'       => [],
                'recent'       => [],
            ];
        }
        $activeCount = (int) ($aiSummary['active_count'] ?? 0);

        $badgeTone = 'none';
        if ($criticalCount > 0) {
            $badgeTone = 'danger';
        } elseif ($warningCount > 0) {
            $badgeTone = 'warning';
        } elseif ($activeCount > 0) {
            $badgeTone = 'info';
        }

        $totalBadgeCount = $criticalCount + $warningCount + $activeCount;

        return [
            'summary' => [
                'unread_critical_count' => $criticalCount,
                'warning_count'         => $warningCount,
                'alerts_count'          => count($alerts),
                'active_tasks_count'    => $activeCount,
                'badge_tone'            => $badgeTone,
                'badge_count'           => $totalBadgeCount,
            ],
            'alerts'   => $alerts,
            'ai_tasks' => $aiSummary,
        ];
    }

    /**
     * Mengambil alert feed dari cache mikro agar polling antar tab admin
     * tidak mengeksekusi seluruh kolektor pada setiap permintaan.
     */
    private function getCachedAlerts(bool $fresh = false): array
    {
        if (!$fresh) {
            try {
                $cached = cache(self::ALERTS_CACHE_KEY);
                if (is_array($cached)) {
                    return $cached;
                }
            } catch (\Throwable $e) {
                log_message('error', 'Notifikasi: gagal membaca cache feed alert. {message}', ['message' => $e->getMessage()]);
            }
        }

        $alerts = array_merge(
            $this->collectWhatsAppAlerts($fresh),
            $this->collectUnassignedRoomAlerts(),
            $this->collectPendingMinutesAlerts(),
            $this->collectSignageIntegrityAlerts(),
            $this->collectWeatherAlerts()
        );

        try {
            cache()->save(self::ALERTS_CACHE_KEY, $alerts, self::ALERTS_CACHE_TTL);
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi: gagal menyimpan cache feed alert. {message}', ['message' => $e->getMessage()]);
        }

        return $alerts;
    }

    /**
     * Mengambil status WhatsApp gateway dengan cache pendek supaya feed
     * tidak melakukan panggilan HTTP keluar pada setiap polling.
     *
     * @return array<string, mixed>
     */
    private function getCachedWaStatus(bool $fresh = false): array
    {
        if (!$fresh) {
            try {
                $cached = cache(self::WA_STATUS_CACHE_KEY);
                if (is_array($cached)) {
                    return $cached;
                }
            } catch (\Throwable $e) {
                log_message('error', 'Notifikasi: gagal membaca cache status WhatsApp. {message}', ['message' => $e->getMessage()]);
            }
        }

        $freshWaStatus = $this->baileysProvider->getStatus();

        try {
            cache()->save(self::WA_STATUS_CACHE_KEY, $freshWaStatus, self::WA_STATUS_CACHE_TTL);
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi: gagal menyimpan cache status WhatsApp. {message}', ['message' => $e->getMessage()]);
        }

        return $freshWaStatus;
    }

    /**
     * Menghapus cache feed alert dan status WhatsApp sehingga polling
     * berikutnya menghitung ulang kondisi terbaru.
     */
    public static function flushFeedCache(): void
    {
        try {
            cache()->delete(self::ALERTS_CACHE_KEY);
            cache()->delete(self::WA_STATUS_CACHE_KEY);
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi: gagal menghapus cache feed. {message}', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Mengumpulkan notifikasi status koneksi WhatsApp Gateway dinas.
     *
     * @return list<array<string, mixed>>
     */
    private function collectWhatsAppAlerts(bool $fresh = false): array
    {
        $alerts = [];
        $waStatus = $this->getCachedWaStatus($fresh);
        $isConfigured = (bool) ($waStatus['configured'] ?? false);
        $isConnected = (bool) ($waStatus['connected'] ?? false);

        if ($isConfigured && ! $isConnected) {
            $fazpassConfigured = $this->otpConfig->fazpassMerchantKey !== '' && $this->otpConfig->fazpassGatewayKey !== '';
            $canFallback = ($this->otpConfig->provider === 'hybrid')
                && $this->otpConfig->fazpassFallbackEnabled
                && $fazpassConfigured;

            $message = $canFallback
                ? 'Nomor WhatsApp dinas terputus. Pengiriman kode OTP sementara dialihkan ke cadangan (Fazpass).'
                : 'Nomor WhatsApp dinas terputus. Anggota dewan saat ini tidak dapat menerima kode OTP login.';

            $alerts[] = [
                'id'            => 'wa-gateway-disconnected',
                'category'      => 'whatsapp',
                'severity'      => 'critical',
                'title'         => 'WhatsApp Gateway Terputus',
                'message'       => $message,
                'action_label'  => 'Tautkan Nomor',
                'action_type'   => 'modal',
                'action_target' => '#modal_wa_pairing',
                'action_url'    => base_url('admin/pengaturan'),
                'created_at'    => date('c'),
            ];
        }

        return $alerts;
    }

    /**
     * Mengumpulkan notifikasi agenda H-0 dan H-1 yang belum memiliki ruangan.
     *
     * @return list<array<string, mixed>>
     */
    private function collectUnassignedRoomAlerts(): array
    {
        $alerts = [];
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $unassigned = [];

        try {
            $db = db_connect();

            if ($db->tableExists('jadwal_banmus')) {
                $banmusRows = $db->table('jadwal_banmus')
                    ->select('id, judul, tanggal, waktu_mulai, waktu_selesai, ruangan_id, lokasi_lainnya, dokumen_banmus_id')
                    ->whereIn('tanggal', [$today, $tomorrow])
                    ->whereIn('status', ['menunggu', 'persiapan', 'berlangsung'])
                    ->get()
                    ->getResultArray();

                foreach ($banmusRows as $row) {
                    if (empty($row['ruangan_id']) && empty(trim((string) ($row['lokasi_lainnya'] ?? '')))) {
                        $unassigned[] = [
                            'source'      => 'banmus',
                            'id'          => $row['id'],
                            'dokumen_id'  => $row['dokumen_banmus_id'],
                            'judul'       => $row['judul'],
                            'tanggal'     => $row['tanggal'],
                            'waktu_mulai' => $row['waktu_mulai'],
                        ];
                    }
                }
            }

            if ($db->tableExists('jadwal_umum')) {
                $umumRows = $db->table('jadwal_umum')
                    ->select('id, judul, tanggal, waktu_mulai, waktu_selesai, ruangan_id, lokasi_lainnya')
                    ->whereIn('tanggal', [$today, $tomorrow])
                    ->whereNotIn('status', ['ditunda', 'dibatalkan', 'selesai'])
                    ->get()
                    ->getResultArray();

                foreach ($umumRows as $row) {
                    if (empty($row['ruangan_id']) && empty(trim((string) ($row['lokasi_lainnya'] ?? '')))) {
                        $unassigned[] = [
                            'source'      => 'umum',
                            'id'          => $row['id'],
                            'dokumen_id'  => null,
                            'judul'       => $row['judul'],
                            'tanggal'     => $row['tanggal'],
                            'waktu_mulai' => $row['waktu_mulai'],
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi: gagal memeriksa agenda tanpa ruangan. {message}', ['message' => $e->getMessage()]);
        }

        if (empty($unassigned)) {
            return [];
        }

        if (count($unassigned) <= 2) {
            foreach ($unassigned as $item) {
                $isToday = $item['tanggal'] === $today;
                $tglLabel = $isToday ? 'Hari Ini' : 'Besok';
                $actionUrl = $item['source'] === 'banmus'
                    ? base_url('admin/jadwal-banmus/' . $item['dokumen_id'])
                    : base_url('admin/jadwal-umum/' . $item['id'] . '/edit');

                $alerts[] = [
                    'id'            => 'unassigned-room-' . $item['source'] . '-' . $item['id'],
                    'category'      => 'unassigned_room',
                    'severity'      => 'warning',
                    'title'         => 'Agenda Belum Menentukan Ruangan',
                    'message'       => "Agenda \"{$item['judul']}\" ({$tglLabel}) belum ditentukan ruangan atau lokasinya.",
                    'action_label'  => 'Tentukan Lokasi',
                    'action_type'   => 'url',
                    'action_target' => null,
                    'action_url'    => $actionUrl,
                    'created_at'    => date('c'),
                ];
            }
        } else {
            $count = count($unassigned);
            $alerts[] = [
                'id'            => 'unassigned-room-summary',
                'category'      => 'unassigned_room',
                'severity'      => 'warning',
                'title'         => 'Agenda Belum Menentukan Ruangan',
                'message'       => "Terdapat {$count} agenda rapat untuk hari ini dan besok yang belum memiliki ruangan pelaksanaan.",
                'action_label'  => 'Lihat Agenda',
                'action_type'   => 'url',
                'action_target' => null,
                'action_url'    => base_url('admin/jadwal-umum'),
                'created_at'    => date('c'),
            ];
        }

        return $alerts;
    }

    /**
     * Mengumpulkan notifikasi risalah AI yang berstatus draft menunggu verifikasi operator.
     *
     * @return list<array<string, mixed>>
     */
    private function collectPendingMinutesAlerts(): array
    {
        $db = db_connect();

        try {
            if (! $db->tableExists('meeting_minutes')) {
                return [];
            }

            $totalDraft = (int) $db->table('meeting_minutes')
                ->where('status_verifikasi', 'draft')
                ->countAllResults();

            if ($totalDraft <= 0) {
                return [];
            }

            return [[
                'id'            => 'pending-minutes-review',
                'category'      => 'pending_minutes',
                'severity'      => 'warning',
                'title'         => 'Risalah AI Menunggu Verifikasi',
                'message'       => "Terdapat {$totalDraft} risalah hasil transkripsi AI yang telah selesai dan siap diverifikasi operator.",
                'action_label'  => 'Tinjau Risalah',
                'action_type'   => 'url',
                'action_target' => null,
                    'action_url'    => base_url('admin/notulen'),
                    'created_at'    => date('c'),
                ]];
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi: gagal menghitung risalah AI menunggu verifikasi. {message}', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Mengumpulkan notifikasi integritas media dan running text digital signage.
     *
     * @return list<array<string, mixed>>
     */
    private function collectSignageIntegrityAlerts(): array
    {
        $alerts = [];
        $db = db_connect();

        try {
            if (! $db->tableExists('settings')) {
                return [];
            }

            $settingModel = new SettingModel();
            $settings = $settingModel->getAllAssoc();

            $mediaFile = trim((string) ($settings['media_file'] ?? ''));
            if ($mediaFile !== '') {
                $fullPath = FCPATH . 'uploads/media/' . $mediaFile;
                if (! is_file($fullPath)) {
                    $alerts[] = [
                        'id'            => 'signage-media-missing',
                        'category'      => 'signage_media',
                        'severity'      => 'warning',
                        'title'         => 'File Media Signage Hilang',
                        'message'       => "File media display \"{$mediaFile}\" tidak ditemukan di server. Layar signage berpotensi kosong.",
                        'action_label'  => 'Periksa Pengaturan',
                        'action_type'   => 'url',
                        'action_target' => null,
                        'action_url'    => base_url('admin/pengaturan'),
                        'created_at'    => date('c'),
                    ];
                }
            }

            $runningTextAktif = (string) ($settings['running_text_aktif'] ?? '0') === '1';
            $runningText = trim((string) ($settings['running_text'] ?? ''));
            if ($runningTextAktif && $runningText === '') {
                $alerts[] = [
                    'id'            => 'signage-running-text-empty',
                    'category'      => 'signage_media',
                    'severity'      => 'warning',
                    'title'         => 'Running Text Signage Kosong',
                    'message'       => 'Running text display signage diaktifkan namun belum memiliki teks pengumuman.',
                    'action_label'  => 'Isi Running Text',
                    'action_type'   => 'url',
                    'action_target' => null,
                    'action_url'    => base_url('admin/pengaturan'),
                    'created_at'    => date('c'),
                ];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi: gagal memeriksa integritas media signage. {message}', ['message' => $e->getMessage()]);
        }

        return $alerts;
    }

    /**
     * Mengumpulkan notifikasi sinkronisasi cuaca BMKG signage.
     *
     * @return list<array<string, mixed>>
     */
    private function collectWeatherAlerts(): array
    {
        try {
            $adm4 = env('BMKG_ADM4') ?: '72.71.01.1004';
            $cacheKey = 'bmkg_weather_' . hash('sha256', $adm4);
            $cache = service('cache');
            $cached = $cache->get($cacheKey);
            $legacyFile = WRITEPATH . 'cache/bmkg_cuaca.json';

            $now = time();
            $hasValidCache = false;

            if (is_array($cached)) {
                $cachedAt = (int) ($cached['cached_at_epoch'] ?? 0);
                $payload = $cached['payload'] ?? null;
                if (is_array($payload) && ($payload['status'] ?? null) === 'success' && ($now - $cachedAt) <= 86400) {
                    $hasValidCache = true;
                }
            } elseif (is_file($legacyFile)) {
                $cachedAt = (int) filemtime($legacyFile);
                if (($now - $cachedAt) <= 86400) {
                    $payload = json_decode((string) file_get_contents($legacyFile), true);
                    if (is_array($payload) && ($payload['status'] ?? null) === 'success') {
                        $hasValidCache = true;
                    }
                }
            }

            if (! $hasValidCache) {
                return [[
                    'id'            => 'bmkg-weather-stale',
                    'category'      => 'weather',
                    'severity'      => 'info',
                    'title'         => 'Sinkronisasi Cuaca BMKG Terkendala',
                    'message'       => 'Prakiraan cuaca pada display signage belum dapat diperbarui dari server BMKG.',
                    'action_label'  => null,
                    'action_type'   => 'url',
                    'action_target' => null,
                    'action_url'    => null,
                    'created_at'    => date('c'),
                ]];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Notifikasi: gagal memeriksa sinkronisasi cuaca BMKG. {message}', ['message' => $e->getMessage()]);
        }

        return [];
    }
}
