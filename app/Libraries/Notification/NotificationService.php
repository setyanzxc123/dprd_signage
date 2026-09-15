<?php

namespace App\Libraries\Notification;

use App\Libraries\Notulen\NotulenService;
use App\Libraries\Otp\Providers\BaileysProvider;
use App\Libraries\Schedule\AgendaWorkspaceService;
use App\Models\SettingModel;
use Config\Otp;

final class NotificationService
{
    private BaileysProvider $baileysProvider;
    private NotulenService $notulenService;
    private Otp $otpConfig;
    private ?AgendaWorkspaceService $agendaWorkspaceService;

    public function __construct(
        ?BaileysProvider $baileysProvider = null,
        ?NotulenService $notulenService = null,
        ?Otp $otpConfig = null,
        ?AgendaWorkspaceService $agendaWorkspaceService = null
    ) {
        $this->otpConfig = $otpConfig ?? config('Otp');
        $this->baileysProvider = $baileysProvider ?? new BaileysProvider(config: $this->otpConfig);
        $this->notulenService = $notulenService ?? new NotulenService();
        $this->agendaWorkspaceService = $agendaWorkspaceService;
    }

    /**
     * Mengambil seluruh feed notifikasi terpadu (alerts & ai tasks).
     *
     * @return array<string, mixed>
     */
    public function getFeed(): array
    {
        $alerts = array_merge(
            $this->collectWhatsAppAlerts(),
            $this->collectScheduleConflictAlerts(),
            $this->collectUnassignedRoomAlerts(),
            $this->collectPendingMinutesAlerts(),
            $this->collectSignageIntegrityAlerts(),
            $this->collectWeatherAlerts()
        );

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
        } catch (\Throwable) {
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
     * Mengumpulkan notifikasi status koneksi WhatsApp Gateway dinas.
     *
     * @return list<array<string, mixed>>
     */
    private function collectWhatsAppAlerts(): array
    {
        $alerts = [];
        $waStatus = $this->baileysProvider->getStatus();
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
     * Mengumpulkan notifikasi bentrok/konflik penggunaan ruangan rapat.
     *
     * @return list<array<string, mixed>>
     */
    private function collectScheduleConflictAlerts(): array
    {
        $alerts = [];

        try {
            $workspaceService = $this->agendaWorkspaceService ?? new AgendaWorkspaceService();
            $month = date('Y-m');
            $workspace = $workspaceService->loadMonth($month);
            $agendas = $workspace['agendas'] ?? [];
            $today = date('Y-m-d');

            $conflictGroups = [];
            foreach ($agendas as $agenda) {
                if (!empty($agenda['has_conflict']) && ($agenda['tanggal'] ?? '') >= $today) {
                    $locKey = $agenda['location_key'] ?: 'lokasi';
                    $groupKey = $agenda['tanggal'] . '_' . $locKey;
                    if (!isset($conflictGroups[$groupKey])) {
                        $conflictGroups[$groupKey] = [
                            'tanggal' => $agenda['tanggal'],
                            'lokasi'  => $agenda['lokasi'] ?? 'Ruang Rapat',
                            'agendas' => [],
                        ];
                    }
                    $startStr = !empty($agenda['waktu_mulai']) ? $agenda['waktu_mulai'] : '';
                    $endStr = !empty($agenda['waktu_selesai']) ? $agenda['waktu_selesai'] : '';
                    $timeStr = ($startStr && $endStr) ? " ({$startStr}-{$endStr})" : '';
                    $conflictGroups[$groupKey]['agendas'][] = $agenda['judul'] . $timeStr;
                }
            }

            foreach ($conflictGroups as $key => $group) {
                $conflictCount = count($group['agendas']);
                $conflictSample = implode(' dan ', array_slice($group['agendas'], 0, 2));
                $moreText = $conflictCount > 2 ? ' (+ ' . ($conflictCount - 2) . ' agenda lain)' : '';
                $tglFormatted = date('d/m/Y', strtotime($group['tanggal']));

                $alerts[] = [
                    'id'            => 'schedule-conflict-' . substr(md5($key), 0, 12),
                    'category'      => 'schedule_conflict',
                    'severity'      => 'critical',
                    'title'         => 'Konflik Ruangan Rapat',
                    'message'       => "Jadwal bentrok di {$group['lokasi']} pada {$tglFormatted}: {$conflictSample}{$moreText}.",
                    'action_label'  => 'Buka Kalender',
                    'action_type'   => 'url',
                    'action_target' => null,
                    'action_url'    => base_url('admin/agenda-workspace/kalender?month=' . date('Y-m', strtotime($group['tanggal'])) . '&lokasi=' . urlencode((string) $group['lokasi'])),
                    'created_at'    => date('c'),
                ];
            }
        } catch (\Throwable) {
            // Defensif jika tabel jadwal belum siap
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
        $db = db_connect();

        $unassigned = [];

        try {
            if ($db->tableExists('jadwal_umum')) {
                $rows = $db->table('jadwal_umum')
                    ->select('id, judul, tanggal, "umum" as source, NULL as dokumen_banmus_id')
                    ->where('tanggal >=', $today)
                    ->where('tanggal <=', $tomorrow)
                    ->whereIn('status', ['menunggu', 'persiapan'])
                    ->groupStart()
                        ->where('ruangan_id IS NULL', null, false)
                        ->orWhere('ruangan_id', 0)
                    ->groupEnd()
                    ->groupStart()
                        ->where('lokasi_lainnya IS NULL', null, false)
                        ->orWhere('TRIM(lokasi_lainnya)', '')
                    ->groupEnd()
                    ->get()
                    ->getResultArray();
                $unassigned = array_merge($unassigned, $rows);
            }

            if ($db->tableExists('jadwal_banmus')) {
                $rows = $db->table('jadwal_banmus')
                    ->select('id, judul, tanggal, "banmus" as source, dokumen_banmus_id')
                    ->where('tanggal >=', $today)
                    ->where('tanggal <=', $tomorrow)
                    ->whereIn('status', ['menunggu', 'persiapan'])
                    ->groupStart()
                        ->where('ruangan_id IS NULL', null, false)
                        ->orWhere('ruangan_id', 0)
                    ->groupEnd()
                    ->groupStart()
                        ->where('lokasi_lainnya IS NULL', null, false)
                        ->orWhere('TRIM(lokasi_lainnya)', '')
                    ->groupEnd()
                    ->get()
                    ->getResultArray();
                $unassigned = array_merge($unassigned, $rows);
            }
        } catch (\Throwable) {
            return [];
        }

        if (empty($unassigned)) {
            return [];
        }

        if (count($unassigned) <= 2) {
            foreach ($unassigned as $item) {
                $isToday = $item['tanggal'] === $today;
                $tglLabel = $isToday ? 'Hari Ini' : 'Besok';
                $editUrl = $item['source'] === 'banmus'
                    ? base_url('admin/jadwal-banmus/' . (int) $item['dokumen_banmus_id'])
                    : base_url("admin/jadwal-umum/{$item['id']}/edit");

                $alerts[] = [
                    'id'            => 'unassigned-room-' . $item['source'] . '-' . $item['id'],
                    'category'      => 'unassigned_room',
                    'severity'      => 'warning',
                    'title'         => 'Agenda Belum Menentukan Ruangan',
                    'message'       => "Agenda \"{$item['judul']}\" ({$tglLabel}) belum ditentukan ruangan atau lokasinya.",
                    'action_label'  => 'Tentukan Lokasi',
                    'action_type'   => 'url',
                    'action_target' => null,
                    'action_url'    => $editUrl,
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
                'action_url'    => base_url('admin/agenda-workspace/kalender?month=' . date('Y-m')),
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
        } catch (\Throwable) {
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
        } catch (\Throwable) {
            // Abaikan kesalahan pembacaan setting
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
        } catch (\Throwable) {
            // Abaikan kegagalan pemeriksaan cuaca
        }

        return [];
    }
}
