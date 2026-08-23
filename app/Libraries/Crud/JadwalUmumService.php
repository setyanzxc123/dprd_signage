<?php

namespace App\Libraries\Crud;

use App\Libraries\Schedule\ScheduleInvitationStorage;
use App\Libraries\Schedule\ScheduleResourceAccess;
use App\Models\JadwalUmumModel;
use App\Models\RuanganModel;
use App\Models\UnitRapatModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Logika CRUD jadwal umum (kegiatan non-Banmus) beserta relasi unit
 * rapat dan berkas undangan. Dipakai bersama oleh controller web dan
 * API: input skalar diterima sebagai array polos, berkas undangan
 * dikirim terpisah sebagai object UploadedFile (atau null).
 */
class JadwalUmumService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Validasi lengkap input jadwal umum.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed>|null $existing baris lama saat update
     * @return array<string, mixed> ['error' => pesan] atau payload ternormalisasi
     */
    public function validatedInput(array $input, ?UploadedFile $invitation = null, ?array $existing = null): array
    {
        $scheduleId = $existing !== null ? (int) ($existing['id'] ?? 0) : null;

        $judul = trim((string) ($input['judul'] ?? ''));
        if ($judul === '' || mb_strlen($judul) > 255) {
            return ['error' => 'Judul wajib diisi dan maksimal 255 karakter.'];
        }

        $tanggal = trim((string) ($input['tanggal'] ?? ''));
        if (! $this->validDate($tanggal)) {
            return ['error' => 'Tanggal wajib diisi dengan format yang valid.'];
        }

        $times = $this->validatedTimes($input);
        if (isset($times['error'])) {
            return $times;
        }

        $location = $this->validatedLocation($input, $scheduleId);
        if (isset($location['error'])) {
            return $location;
        }
        if ($location['ruangan_id'] !== null
            && ($times['waktu_mulai'] === null || $times['waktu_selesai'] === null)) {
            return ['error' => 'Jam mulai dan selesai wajib diisi jika memakai ruangan DPRD.'];
        }

        $unitIds = $this->postedUnitIds($input);
        if ($unitIds !== [] && $this->invalidUnitIds($unitIds) !== []) {
            return ['error' => 'Kelompok peserta tidak valid atau sudah nonaktif.'];
        }
        if ($unitIds !== [] && $this->unitIdsWithoutActiveMembers($unitIds) !== []) {
            return ['error' => 'Kelompok peserta yang dipilih harus mempunyai anggota aktif.'];
        }

        $pihakEksternal = trim((string) ($input['pihak_eksternal'] ?? ''));
        if (mb_strlen($pihakEksternal) > 255) {
            return ['error' => 'Pihak eksternal maksimal 255 karakter.'];
        }
        $keterangan = trim((string) ($input['keterangan'] ?? ''));
        if (mb_strlen($keterangan) > 5000) {
            return ['error' => 'Keterangan maksimal 5.000 karakter.'];
        }

        $invitationCheck = (new ScheduleInvitationStorage())->validate($invitation);
        if (isset($invitationCheck['error'])) {
            return ['error' => $invitationCheck['error']];
        }
        $materialUrl = $this->validatedOptionalUrl((string) ($input['materi_url'] ?? ''), 'Tautan bahan rapat tidak valid.');
        if (isset($materialUrl['error'])) {
            return ['error' => $materialUrl['error']];
        }
        $streamUrl = $this->validatedOptionalUrl((string) ($input['stream_url'] ?? ''), 'Tautan live streaming tidak valid.');
        if (isset($streamUrl['error'])) {
            return ['error' => $streamUrl['error']];
        }

        if ($location['ruangan_id'] !== null
            && (new JadwalUmumModel())->hasRoomConflict(
                $location['ruangan_id'],
                $tanggal,
                $times['waktu_mulai'],
                $times['waktu_selesai'],
                $scheduleId,
            )) {
            return ['error' => 'Ruangan sudah dipakai pada tanggal dan rentang waktu tersebut.'];
        }

        return [
            'payload' => [
                'judul'           => $judul,
                'tanggal'         => $tanggal,
                'waktu_mulai'     => $times['waktu_mulai'],
                'waktu_selesai'   => $times['waktu_selesai'],
                'ruangan_id'      => $location['ruangan_id'],
                'lokasi_lainnya'  => $location['lokasi_lainnya'],
                'pihak_eksternal' => $pihakEksternal !== '' ? $pihakEksternal : null,
                'is_publik'       => ($input['is_publik'] ?? null) === '1' ? 1 : 0,
                'keterangan'      => $keterangan !== '' ? $keterangan : null,
                'materi_url'      => $materialUrl['url'],
                'materi_akses'    => ScheduleResourceAccess::normalize($input['materi_akses'] ?? null, ScheduleResourceAccess::PARTICIPANT),
                'stream_url'      => $streamUrl['url'],
                'stream_akses'    => ScheduleResourceAccess::normalize($input['stream_akses'] ?? null, ScheduleResourceAccess::MEMBER),
            ],
            'unit_ids' => $unitIds,
            'invitation_upload' => $invitation,
            'remove_invitation' => ($input['hapus_undangan'] ?? null) === '1',
        ];
    }

    /**
     * Menyimpan berkas undangan hasil validasi.
     *
     * @param array<string, mixed> $input hasil validatedInput
     * @return array{payload?: array<string, mixed>, new_file?: ?string, error?: string}
     */
    public function storeInvitationUpload(array $input): array
    {
        if (($input['remove_invitation'] ?? false) === true && ($input['invitation_upload'] ?? null) === null) {
            return ['payload' => ['undangan_file' => null, 'undangan_nama_asli' => null], 'new_file' => null];
        }
        if (($input['invitation_upload'] ?? null) === null) {
            return ['payload' => [], 'new_file' => null];
        }

        try {
            $stored = (new ScheduleInvitationStorage())->store($input['invitation_upload']);

            return [
                'payload' => [
                    'undangan_file' => $stored['file'],
                    'undangan_nama_asli' => $stored['original_name'],
                ],
                'new_file' => $stored['file'],
            ];
        } catch (\Throwable $exception) {
            log_message('error', 'Gagal menyimpan undangan Jadwal Umum: {message}', ['message' => $exception->getMessage()]);

            return ['error' => 'PDF undangan gagal disimpan. Silakan coba kembali.'];
        }
    }

    /**
     * Simpan jadwal baru beserta relasi unit dan undangan.
     *
     * @param array<string, mixed> $input hasil validatedInput
     * @return int|string id baru, atau pesan error
     */
    public function create(array $input): int|string
    {
        $storedInvitation = $this->storeInvitationUpload($input);
        if (isset($storedInvitation['error'])) {
            return $storedInvitation['error'];
        }
        $payload = array_merge($input['payload'], $storedInvitation['payload']);

        $this->db->transStart();
        $id = (new JadwalUmumModel())->insert($payload, true);
        if ($id !== false && (int) $id > 0) {
            $this->syncUnits((int) $id, $input['unit_ids']);
        }
        $this->db->transComplete();

        if ($id === false || (int) $id < 1 || ! $this->db->transStatus()) {
            (new ScheduleInvitationStorage())->delete($storedInvitation['new_file'] ?? null);

            return 'Jadwal Umum gagal disimpan. Silakan coba kembali.';
        }

        return (int) $id;
    }

    /**
     * Perbarui jadwal; berkas undangan lama diganti/dihapus bila perlu.
     *
     * @param array<string, mixed> $input hasil validatedInput
     * @param array<string, mixed> $existing baris lama
     * @return ?string pesan error bila gagal
     */
    public function update(int $id, array $input, array $existing): ?string
    {
        $storedInvitation = $this->storeInvitationUpload($input);
        if (isset($storedInvitation['error'])) {
            return $storedInvitation['error'];
        }
        $payload = array_merge($input['payload'], $storedInvitation['payload']);

        $this->db->transStart();
        $updated = (new JadwalUmumModel())->update($id, $payload);
        if ($updated) {
            $this->syncUnits($id, $input['unit_ids']);
        }
        $this->db->transComplete();

        if (! $updated || ! $this->db->transStatus()) {
            (new ScheduleInvitationStorage())->delete($storedInvitation['new_file'] ?? null);

            return 'Jadwal Umum gagal diperbarui. Silakan coba kembali.';
        }

        if (($existing['undangan_file'] ?? null) !== ($payload['undangan_file'] ?? $existing['undangan_file'] ?? null)) {
            (new ScheduleInvitationStorage())->delete($existing['undangan_file'] ?? null);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $existing baris lama
     */
    public function delete(array $existing): void
    {
        $id = (int) $existing['id'];

        $this->db->transStart();
        $this->db->table('jadwal_umum_unit_rapat')->where('jadwal_umum_id', $id)->delete();
        (new JadwalUmumModel())->delete($id);
        $this->db->transComplete();

        (new ScheduleInvitationStorage())->delete($existing['undangan_file'] ?? null);
    }

    /** @return list<int> */
    public function unitIdsForSchedule(int $scheduleId): array
    {
        return array_map('intval', array_column(
            $this->db->table('jadwal_umum_unit_rapat')->select('unit_rapat_id')
                ->where('jadwal_umum_id', $scheduleId)->get()->getResultArray(),
            'unit_rapat_id',
        ));
    }

    /**
     * @param list<int> $scheduleIds
     * @return array<int, list<string>>
     */
    public function unitNamesByScheduleIds(array $scheduleIds): array
    {
        $scheduleIds = array_values(array_filter(array_map('intval', $scheduleIds)));
        if ($scheduleIds === []) {
            return [];
        }

        $rows = $this->db->table('jadwal_umum_unit_rapat jur')
            ->select('jur.jadwal_umum_id, ur.nama')
            ->join('unit_rapat ur', 'ur.id = jur.unit_rapat_id')
            ->whereIn('jur.jadwal_umum_id', $scheduleIds)
            ->orderBy('ur.urutan', 'ASC')->orderBy('ur.nama', 'ASC')
            ->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['jadwal_umum_id']][] = (string) $row['nama'];
        }

        return $map;
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function validatedTimes(array $input): array
    {
        $start = trim((string) ($input['waktu_mulai'] ?? ''));
        $end = trim((string) ($input['waktu_selesai'] ?? ''));

        if ($start === '' && $end === '') {
            return ['waktu_mulai' => null, 'waktu_selesai' => null];
        }
        if ($start === '') {
            return ['error' => 'Jam selesai tidak boleh diisi tanpa jam mulai.'];
        }
        if (! $this->validTime($start) || ($end !== '' && ! $this->validTime($end))) {
            return ['error' => 'Format jam pelaksanaan tidak valid.'];
        }
        if ($end !== '' && $end <= $start) {
            return ['error' => 'Jam selesai harus setelah jam mulai.'];
        }

        return [
            'waktu_mulai'   => $start . (strlen($start) === 5 ? ':00' : ''),
            'waktu_selesai' => $end !== '' ? $end . (strlen($end) === 5 ? ':00' : '') : null,
        ];
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function validatedLocation(array $input, ?int $scheduleId): array
    {
        $mode = ($input['lokasi_mode'] ?? null) === 'lainnya' ? 'lainnya' : 'ruangan';

        if ($mode === 'lainnya') {
            $location = trim((string) ($input['lokasi_lainnya'] ?? ''));
            if ($location === '' || mb_strlen($location) > 255) {
                return ['error' => 'Lokasi lainnya wajib diisi dan maksimal 255 karakter.'];
            }

            return ['ruangan_id' => null, 'lokasi_lainnya' => $location];
        }

        $roomId = (int) ($input['ruangan_id'] ?? 0);
        if ($roomId < 1) {
            return ['error' => 'Pilih ruangan DPRD atau gunakan lokasi lainnya.'];
        }

        $room = (new RuanganModel())->find($roomId);
        $current = $scheduleId === null ? null : (new JadwalUmumModel())->find($scheduleId);
        $currentRoomId = (int) ($current['ruangan_id'] ?? 0);
        if ($room === null || ((int) ($room['tersedia'] ?? 0) !== 1 && $roomId !== $currentRoomId)) {
            return ['error' => 'Ruangan yang dipilih tidak valid atau tidak tersedia.'];
        }

        return ['ruangan_id' => $roomId, 'lokasi_lainnya' => null];
    }

    /** @param array<string, mixed> $input @return list<int> */
    private function postedUnitIds(array $input): array
    {
        $ids = $input['target_unit_rapat'] ?? [];
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }

    /** @param list<int> $unitIds @return list<int> */
    private function invalidUnitIds(array $unitIds): array
    {
        $validIds = array_map('intval', array_column(
            (new UnitRapatModel())->select('id')->where('aktif', 1)->whereIn('id', $unitIds)->findAll(),
            'id',
        ));

        return array_values(array_diff($unitIds, $validIds));
    }

    /** @param list<int> $unitIds @return list<int> */
    private function unitIdsWithoutActiveMembers(array $unitIds): array
    {
        if (! $this->db->tableExists('anggota_unit_rapat') || ! $this->db->tableExists('anggota')) {
            return $unitIds;
        }

        $rows = $this->db->table('anggota_unit_rapat aur')
            ->distinct()->select('aur.unit_rapat_id')
            ->join('anggota a', 'a.id = aur.anggota_id')
            ->whereIn('aur.unit_rapat_id', $unitIds)
            ->where('a.aktif', 1)
            ->get()->getResultArray();

        return array_values(array_diff($unitIds, array_map('intval', array_column($rows, 'unit_rapat_id'))));
    }

    /** @param list<int> $unitIds */
    private function syncUnits(int $scheduleId, array $unitIds): void
    {
        $this->db->table('jadwal_umum_unit_rapat')->where('jadwal_umum_id', $scheduleId)->delete();
        if ($unitIds === []) {
            return;
        }

        $createdAt = date('Y-m-d H:i:s');
        $this->db->table('jadwal_umum_unit_rapat')->insertBatch(array_map(
            static fn (int $unitId): array => [
                'jadwal_umum_id' => $scheduleId,
                'unit_rapat_id'  => $unitId,
                'created_at'     => $createdAt,
            ],
            $unitIds,
        ));
    }

    private function validDate(string $value): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year);
    }

    private function validTime(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value) === 1;
    }

    /** @return array{url?: ?string, error?: string} */
    private function validatedOptionalUrl(string $url, string $message): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['url' => null];
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (filter_var($url, FILTER_VALIDATE_URL) === false || ! in_array($scheme, ['http', 'https'], true)) {
            return ['error' => $message];
        }

        return ['url' => $url];
    }
}
