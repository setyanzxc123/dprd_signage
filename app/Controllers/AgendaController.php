<?php

namespace App\Controllers;

use App\Libraries\Schedule\Persistence\DatabaseScheduleReadRepository;
use App\Models\BanmusDocumentModel;
use App\Models\JadwalBanmusModel;
use App\Models\MemberAccountModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

class AgendaController extends BaseController
{
    public function root()
    {
        return redirect()->to(base_url('agenda'), 302);
    }

    public function index()
    {
        $member = $this->activeMember();
        $isMember = $member !== null;
        $isAdmin = ! $isMember && session()->has('auth_user');
        $banmusProjections = $this->databaseDriverAvailable()
            ? $this->portalBanmusProjections($member)
            : [];

        return $this->privateResponse()->setBody(view('agenda/index', [
            'namaInstansi' => 'DPRD Provinsi Sulawesi Tengah',
            'logoUrl'      => base_url('assets/images/logo_dprd.jpg'),
            'portalUrl'    => base_url('agenda'),
            'apiUrl'       => base_url($isMember ? 'api/v1/anggota/jadwal' : 'api/v1/publik/jadwal'),
            'generalApiUrl' => base_url($isMember
                ? 'api/v1/anggota/agenda-umum'
                : 'api/v1/publik/agenda-umum'),
            'member'       => $member,
            'isAdmin'      => $isAdmin,
            'banmusProjections' => $banmusProjections,
        ]));
    }

    public function banmus()
    {
        $member = $this->activeMember();
        $includeInternal = $member !== null;
        $isAdmin = ! $includeInternal && session()->has('auth_user');
        $model = $this->databaseDriverAvailable() ? new BanmusDocumentModel() : null;
        if ($model !== null) {
            (new JadwalBanmusModel())->autoUpdateStatuses();
        }
        $availableYears = $model?->availableYears($includeInternal) ?? [];

        $requestedYear = trim((string) $this->request->getGet('tahun'));
        $requestedYearIsAvailable = preg_match('/^\d{4}$/', $requestedYear) === 1
            && ($availableYears === [] || in_array((int) $requestedYear, $availableYears, true));
        $selectedYear = $requestedYearIsAvailable
            ? (int) $requestedYear
            : ($availableYears[0] ?? (int) date('Y'));

        $requestedSemester = trim((string) $this->request->getGet('semester'));
        $selectedSemester = in_array($requestedSemester, ['1', '2'], true)
            ? (int) $requestedSemester
            : null;

        return $this->privateResponse()->setBody(view('agenda/banmus', [
            'logoUrl'           => base_url('assets/images/logo_dprd.jpg'),
            'portalUrl'         => base_url('agenda'),
            'member'            => $member,
            'isAdmin'           => $isAdmin,
            'documents'         => $model?->findForPortal($includeInternal, $selectedYear, $selectedSemester) ?? [],
            'availableYears'    => $availableYears,
            'selectedYear'      => $selectedYear,
            'selectedSemester'  => $selectedSemester,
        ]));
    }

    public function banmusDocument(int $id)
    {
        if (! $this->databaseDriverAvailable()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $model = new BanmusDocumentModel();
        $document = $model->find($id);
        if ($document === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $member = $this->activeMember();
        if ((int) ($document['is_publik'] ?? 0) !== 1 && $member === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $externalUrl = trim((string) ($document['dokumen_url'] ?? ''));
        if ($externalUrl !== '') {
            $scheme = strtolower((string) parse_url($externalUrl, PHP_URL_SCHEME));
            if (filter_var($externalUrl, FILTER_VALIDATE_URL) === false
                || ! in_array($scheme, ['http', 'https'], true)) {
                throw PageNotFoundException::forPageNotFound();
            }

            return $this->privateResponse()->redirect($externalUrl, 'auto', 302);
        }

        $fileName = (string) ($document['dokumen_file'] ?? '');
        $safeFileName = basename($fileName);
        if ($safeFileName === ''
            || $safeFileName !== $fileName
            || preg_match('/^[a-f0-9]{40}\.pdf$/', $safeFileName) !== 1) {
            throw PageNotFoundException::forPageNotFound();
        }

        $path = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'sk-banmus' . DIRECTORY_SEPARATOR . $safeFileName;
        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $downloadName = basename((string) ($document['dokumen_nama_asli'] ?: 'SK-Banmus.pdf'));
        $downloadName = str_replace(['"', "\r", "\n"], '', $downloadName);
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->privateResponse()
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $downloadName . '"')
            ->setHeader('Content-Length', (string) strlen($contents))
            ->setBody($contents);
    }

    public function legacy()
    {
        return redirect()->to(base_url('agenda'), 302);
    }

    private function privateResponse(): ResponseInterface
    {
        return $this->response
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Pragma', 'no-cache')
            ->appendHeader('Vary', 'Cookie');
    }

    private function activeMember(): ?array
    {
        $auth = session()->get('member_auth');
        if (! is_array($auth)) {
            return null;
        }

        $account = (new MemberAccountModel())->findActiveSessionAccount(
            (int) ($auth['account_id'] ?? 0),
            (int) ($auth['anggota_id'] ?? 0),
        );
        if ($account === null) {
            session()->remove('member_auth');

            return null;
        }

        session()->set('member_auth', [
            'account_id' => (int) $account['account_id'],
            'anggota_id' => (int) $account['anggota_id'],
            'name'       => (string) $account['name'],
        ]);

        return $account;
    }

    /**
     * Proyeksi hanya ditambahkan ke shell portal. API jadwal dan signage tetap
     * membaca agenda Banmus yang sudah terjadwal.
     *
     * @return list<array<string, mixed>>
     */
    private function portalBanmusProjections(?array $member): array
    {
        $includeInternal = $member !== null;
        $documentModel = new BanmusDocumentModel();
        $currentYear = (int) date('Y');
        $displayYears = array_values(array_filter(
            $documentModel->availableYears($includeInternal),
            static fn (int $year): bool => $year >= $currentYear && $year <= $currentYear + 1,
        ));
        if ($displayYears === []) {
            return [];
        }
        sort($displayYears);

        $projections = [];
        foreach ($displayYears as $year) {
            foreach ($documentModel->findForPortal($includeInternal, $year) as $document) {
                foreach ($document['items'] as $item) {
                    if (($item['status'] ?? null) !== 'proyeksi'
                        || ($item['jenis_agenda'] ?? JadwalBanmusModel::TYPE_MEETING) !== JadwalBanmusModel::TYPE_MEETING) {
                        continue;
                    }

                    $item['document'] = $document;
                    $projections[] = $item;
                }
            }
        }
        usort($projections, static fn (array $left, array $right): int => [
            (int) $left['document']['tahun'],
            (int) $left['document']['semester'],
            (int) $left['urutan'],
            (int) $left['id'],
        ] <=> [
            (int) $right['document']['tahun'],
            (int) $right['document']['semester'],
            (int) $right['urutan'],
            (int) $right['id'],
        ]);

        $banmusModel = new JadwalBanmusModel();
        $projections = $banmusModel->attachUnitIds($projections);
        $memberUnitIds = $member === null
            ? []
            : (new DatabaseScheduleReadRepository())->findMemberUnitIds((int) ($member['anggota_id'] ?? 0));

        return array_map(static function (array $item) use ($memberUnitIds): array {
            $document = $item['document'];
            $unitIds = array_map('intval', $item['unit_ids'] ?? []);
            $date = trim((string) ($item['tanggal'] ?? ''));

            return [
                'id'               => (int) $item['id'],
                'source_id'        => (int) $item['id'],
                'source'           => 'banmus_projection',
                'judul'            => (string) $item['agenda'],
                'keterangan'       => $item['catatan'],
                'tanggal'          => $date !== '' ? $date : null,
                'waktu_mulai'      => ! empty($item['jam_mulai'])
                    ? substr((string) $item['jam_mulai'], 0, 5)
                    : '',
                'waktu_selesai'    => ! empty($item['jam_selesai'])
                    ? substr((string) $item['jam_selesai'], 0, 5)
                    : '',
                'status'           => 'proyeksi',
                'periode_label'    => (string) ($item['periode_label'] ?? ''),
                'ruangan'          => trim((string) ($item['lokasi_lainnya'] ?? '')),
                'unit_ids'         => $unitIds,
                'komisi'           => '',
                'is_public'        => ($item['publikasi'] ?? 'internal') === 'publik'
                    && (int) ($document['is_publik'] ?? 0) === 1,
                'is_participant'   => $memberUnitIds !== []
                    && array_intersect($unitIds, $memberUnitIds) !== [],
                'has_materi'       => false,
                'has_stream'       => false,
                'document_title'   => (string) ($document['judul'] ?? 'SK Banmus'),
                'document_number'  => (string) ($document['nomor_sk'] ?? '-'),
                'document_year'    => (int) $document['tahun'],
                'projection_url'   => base_url('agenda/jadwal-banmus?' . http_build_query([
                    'tahun'    => (int) $document['tahun'],
                    'semester' => (int) $document['semester'],
                ])),
            ];
        }, $projections);
    }

    private function databaseDriverAvailable(): bool
    {
        $config = config(\Config\Database::class);
        $group = $config->defaultGroup;
        $settings = $config->{$group} ?? [];
        $driver = $settings['DBDriver'] ?? '';
        $requiredExtensions = [
            'MySQLi'  => 'mysqli',
            'Postgre' => 'pgsql',
            'SQLite3' => 'sqlite3',
            'SQLSRV'  => 'sqlsrv',
            'OCI8'    => 'oci8',
        ];

        return ! isset($requiredExtensions[$driver]) || extension_loaded($requiredExtensions[$driver]);
    }
}
