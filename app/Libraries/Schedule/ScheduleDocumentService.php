<?php

namespace App\Libraries\Schedule;

use App\Models\JadwalBanmusModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Penyaji dokumen terautentikasi agenda: undangan rapat (PDF lokal di
 * writable/uploads/agenda-invitations) dan dokumen SK banmus (URL
 * eksternal atau PDF lokal di writable/uploads/sk-banmus). Aturan
 * pencarian berkas dipusatkan di sini agar web dan API berperilaku
 * identik.
 */
final class ScheduleDocumentService
{
    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private readonly BaseConnection $db;

    /**
     * Undangan rapat milik jadwal — banmus wajib berjenis rapat dan
     * berstatus terjadwal. Null bila baris atau berkasnya tidak ada.
     *
     * @return array{path: string, download_name: string}|null
     */
    public function findInvitation(string $source, int $id): ?array
    {
        if ($id < 1
            || ! in_array($source, [ScheduleResourceLinkService::SOURCE_BANMUS, ScheduleResourceLinkService::SOURCE_GENERAL], true)) {
            return null;
        }

        $table = $source === ScheduleResourceLinkService::SOURCE_BANMUS ? 'jadwal_banmus' : 'jadwal_umum';
        $builder = $this->db->table($table)
            ->select('undangan_file, undangan_nama_asli')
            ->where('id', $id)
            ->where('undangan_file IS NOT NULL', null, false)
            ->where('undangan_file !=', '');
        if ($table === 'jadwal_banmus') {
            $builder->where('jenis_agenda', JadwalBanmusModel::TYPE_MEETING)
                ->whereIn('status', JadwalBanmusModel::SCHEDULED_STATUSES)
                ->where('deleted_at', null);
        }
        $row = $builder->get(1)->getRowArray();
        if ($row === null) {
            return null;
        }

        $path = (new ScheduleInvitationStorage())->path($row['undangan_file'] ?? null);
        if ($path === null) {
            return null;
        }

        return [
            'path'          => $path,
            'download_name' => self::sanitizeDownloadName($row['undangan_nama_asli'] ?? null, 'undangan-rapat.pdf'),
        ];
    }

    /**
     * Resolusi dokumen SK banmus: URL eksternal bila terisi (harus
     * http/https valid), selain itu berkas PDF lokal. Null bila dokumen
     * tidak dapat disajikan.
     *
     * @param array<string, mixed> $document
     * @return array{url: string}|array{path: string, download_name: string}|null
     */
    public function resolveSkDocument(array $document): ?array
    {
        $externalUrl = trim((string) ($document['dokumen_url'] ?? ''));
        if ($externalUrl !== '') {
            $scheme = strtolower((string) parse_url($externalUrl, PHP_URL_SCHEME));
            if (filter_var($externalUrl, FILTER_VALIDATE_URL) === false
                || ! in_array($scheme, ['http', 'https'], true)) {
                return null;
            }

            return ['url' => $externalUrl];
        }

        $fileName = (string) ($document['dokumen_file'] ?? '');
        $safeFileName = basename($fileName);
        if ($safeFileName === ''
            || $safeFileName !== $fileName
            || preg_match('/^[a-f0-9]{40}\.pdf$/', $safeFileName) !== 1) {
            return null;
        }

        $path = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'sk-banmus' . DIRECTORY_SEPARATOR . $safeFileName;
        if (! is_file($path)) {
            return null;
        }

        return [
            'path'          => $path,
            'download_name' => self::sanitizeDownloadName($document['dokumen_nama_asli'] ?? null, 'SK-Banmus.pdf'),
        ];
    }

    private static function sanitizeDownloadName(mixed $value, string $fallback): string
    {
        return str_replace(['"', "\r", "\n"], '', basename((string) ($value ?: $fallback)));
    }
}
