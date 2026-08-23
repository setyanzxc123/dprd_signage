<?php

use App\Libraries\Crud\JadwalBanmusService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian endpoint API CRUD dokumen SK Banmus: otorisasi bearer per
 * grup, paginasi/pencarian + jumlah item, aturan bisnis nomor/semester
 * unik, dan siklus berkas PDF SK (persistSk/replaceDocument/
 * deleteDocument) lewat UploadedFile test-double.
 *
 * @internal
 */
final class ApiJadwalBanmusCrudTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private const ADMIN_TOKEN = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const ANGGOTA_TOKEN = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private BaseConnection $apiDb;
    private Forge $apiForge;
    /** @var list<string> */
    private array $filesToClean = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API Banmus.');
        }

        $this->apiDb = Database::connect('tests');
        $this->apiForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedIdentities();
        $this->seedDocuments();
        $this->seedMasterData();
    }

    protected function tearDown(): void
    {
        if (isset($this->apiForge)) {
            $this->dropTables();
        }

        service('superglobals')->setFilesArray([]);
        foreach ($this->filesToClean as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->filesToClean = [];

        parent::tearDown();
    }

    public function testEndpointRequiresBearerToken(): void
    {
        $this->get('/api/v1/jadwal-banmus')->assertStatus(401);
        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/jadwal-banmus')
            ->assertStatus(401);
    }

    public function testAnggotaTokenIsForbidden(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ANGGOTA_TOKEN])
            ->get('/api/v1/jadwal-banmus');

        $response->assertStatus(403);
        $this->assertSame('error', json_decode((string) $response->response()->getBody(), true)['status']);
    }

    public function testListPaginatesSearchesAndCountsItems(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/jadwal-banmus');

        $response->assertOK();
        $body = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame(2, $body['meta']['total']);

        // Terbaru dulu (tahun, semester DESC) + hitungan item per SK.
        $this->assertSame('SK/2026/001', $body['data'][0]['nomor_sk']);
        $this->assertSame(2, $body['data'][0]['jumlah_item']);
        $this->assertSame('SK/2025/002', $body['data'][1]['nomor_sk']);
        $this->assertSame(0, $body['data'][1]['jumlah_item']);

        $search = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/jadwal-banmus?q=SK/2025');
        $searchBody = json_decode((string) $search->response()->getBody(), true);
        $this->assertSame(1, $searchBody['meta']['total']);
        $this->assertSame('SK/2025/002', $searchBody['data'][0]['nomor_sk']);

        $paged = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/jadwal-banmus?per_page=1&page=2');
        $pagedBody = json_decode((string) $paged->response()->getBody(), true);
        $this->assertCount(1, $pagedBody['data']);
        $this->assertSame(2, $pagedBody['meta']['total_pages']);
    }

    public function testCrudValidatesAndUpdatesViaApi(): void
    {
        $bearer = ['Authorization' => 'Bearer ' . self::ADMIN_TOKEN];
        $tempPath = (string) tempnam(sys_get_temp_dir(), 'sk-api-');
        file_put_contents($tempPath, '%PDF-1.4 pengujian');

        // Create tanpa berkas → wajib unggah PDF.
        $noFile = $this->withHeaders($bearer)->post('/api/v1/jadwal-banmus', [
            'nomor_sk' => 'SK/API/2027/1',
            'tahun'    => '2027',
            'semester' => '1',
        ]);
        $noFile->assertStatus(422);
        $this->assertSame(
            'File SK dalam format PDF wajib diunggah.',
            json_decode((string) $noFile->response()->getBody(), true)['message']
        );

        // Berkas bukan hasil upload HTTP asli → ditolak penyimpanan.
        $this->setFakeUpload($tempPath, 'dokumen.txt', 'text/plain');
        $invalid = $this->withHeaders($bearer)->post('/api/v1/jadwal-banmus', [
            'nomor_sk' => 'SK/API/2027/1',
            'tahun'    => '2027',
            'semester' => '1',
        ]);
        $invalid->assertStatus(422);
        $this->assertSame(
            'Unggahan PDF gagal diproses. Silakan pilih ulang dokumen.',
            json_decode((string) $invalid->response()->getBody(), true)['message']
        );

        // Semester yang sama pada tahun sama tidak boleh dobel.
        $duplicate = $this->withHeaders($bearer)->post('/api/v1/jadwal-banmus', [
            'nomor_sk' => 'SK/API/2026/9',
            'tahun'    => '2026',
            'semester' => '1',
        ]);
        $duplicate->assertStatus(422);
        $this->assertStringContainsString(
            'Semester 1 Tahun 2026 sudah terdaftar',
            json_decode((string) $duplicate->response()->getBody(), true)['message']
        );

        // Update metadata tanpa berkas mempertahankan PDF lama.
        $this->withHeaders($bearer)
            ->withBodyFormat('json')
            ->put('/api/v1/jadwal-banmus/999', ['nomor_sk' => 'X', 'tahun' => '2026', 'semester' => '1'])
            ->assertStatus(404);

        $invalidYear = $this->withHeaders($bearer)
            ->withBodyFormat('json')
            ->put('/api/v1/jadwal-banmus/1', ['nomor_sk' => 'SK/2026/001-REV', 'tahun' => 'abc', 'semester' => '2']);
        $invalidYear->assertStatus(422);

        $updated = $this->withHeaders($bearer)
            ->withBodyFormat('json')
            ->put('/api/v1/jadwal-banmus/1', [
                'nomor_sk' => 'SK/2026/001-REV',
                'tahun'    => 2026,
                'semester' => 2,
                'catatan'  => 'Diperbarui dari API.',
            ]);
        $updated->assertOK();
        $row = $this->apiDb->table('dokumen_banmus')->where('id', 1)->get()->getRowArray();
        $this->assertSame('SK/2026/001-REV', $row['nomor_sk']);
        $this->assertSame(2, (int) $row['semester']);
        $this->assertSame(str_repeat('c', 40) . '.pdf', $row['dokumen_file']);

        // Show 404 + jumlah item pada show.
        $this->withHeaders($bearer)->get('/api/v1/jadwal-banmus/999')->assertStatus(404);
        $shown = $this->withHeaders($bearer)->get('/api/v1/jadwal-banmus/1');
        $shown->assertOK();
        $this->assertSame(2, json_decode((string) $shown->response()->getBody(), true)['data']['jumlah_item']);

        // Sub-resource dokumen: 404 & wajib berkas.
        $this->setFakeUpload($tempPath);
        $this->withHeaders($bearer)->post('/api/v1/jadwal-banmus/999/dokumen')->assertStatus(404);
        $this->setFakeUpload(null);
        $missing = $this->withHeaders($bearer)->post('/api/v1/jadwal-banmus/1/dokumen');
        $missing->assertStatus(422);
        $this->assertSame(
            'Berkas dokumen SK wajib diunggah.',
            json_decode((string) $missing->response()->getBody(), true)['message']
        );

        // Delete fisik beserta itemnya.
        $deleted = $this->withHeaders($bearer)->delete('/api/v1/jadwal-banmus/1');
        $deleted->assertOK();
        $this->assertSame('deleted', json_decode((string) $deleted->response()->getBody(), true)['outcome']);
        $this->assertSame(0, $this->apiDb->table('dokumen_banmus')->where('id', 1)->countAllResults());

        unlink($tempPath);
    }

    public function testServicePersistsAndReplacesSkDocument(): void
    {
        $service = new JadwalBanmusService($this->apiDb);
        $storageDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'sk-banmus';

        $first = $this->documentUploadDouble('sk-uji.pdf');
        $validated = $service->validatedSkForm(
            ['nomor_sk' => 'SK/UJI/2027/1', 'tahun' => '2027', 'semester' => '1'],
            $first['file'],
        );
        $this->assertArrayNotHasKey('error', $validated);

        $result = $service->persistSk(null, $validated);
        $this->assertArrayNotHasKey('error', $result);
        $id = (int) $result['id'];

        $row = $this->apiDb->table('dokumen_banmus')->where('id', $id)->get()->getRowArray();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}\.pdf$/', (string) $row['dokumen_file']);
        $this->assertSame('sk-uji.pdf', $row['dokumen_nama_asli']);
        $firstPath = $storageDir . DIRECTORY_SEPARATOR . $row['dokumen_file'];
        $this->filesToClean[] = $firstPath;
        $this->assertFileExists($firstPath);

        // Ganti dokumen: berkas lama dihapus setelah yang baru tersimpan.
        $second = $this->documentUploadDouble('sk-uji-revisi.pdf');
        $this->assertNull($service->replaceDocument($id, $second['file']));

        $row = $this->apiDb->table('dokumen_banmus')->where('id', $id)->get()->getRowArray();
        $this->assertSame('sk-uji-revisi.pdf', $row['dokumen_nama_asli']);
        $secondPath = $storageDir . DIRECTORY_SEPARATOR . $row['dokumen_file'];
        $this->filesToClean[] = $secondPath;
        $this->assertNotSame($firstPath, $secondPath);
        $this->assertFileExists($secondPath);
        $this->assertFileDoesNotExist($firstPath);

        $this->assertTrue($service->deleteDocument($row));
        $this->assertSame(0, $this->apiDb->table('dokumen_banmus')->where('id', $id)->countAllResults());
        $this->assertFileDoesNotExist($secondPath);
    }

    public function testItemCrudFlowViaApi(): void
    {
        $bearer = ['Authorization' => 'Bearer ' . self::ADMIN_TOKEN];
        $tanggal = date('Y-m-d', strtotime('+30 days'));

        // List item terlingkupi dokumen; item tanpa pivot tidak punya unit.
        $this->withHeaders($bearer)->get('/api/v1/jadwal-banmus/999/item')->assertStatus(404);
        $list = $this->withHeaders($bearer)->get('/api/v1/jadwal-banmus/1/item');
        $list->assertOK();
        $listBody = json_decode((string) $list->response()->getBody(), true);
        $this->assertCount(2, $listBody['data']);
        $this->assertSame([], $listBody['data'][0]['unit_ids']);

        // Item proyeksi (data pelaksanaan belum lengkap).
        $projection = $this->withHeaders($bearer)->withBodyFormat('json')->post('/api/v1/jadwal-banmus/1/item', [
            'agenda'        => 'Rapat Dengar Pendapat Publik',
            'periode_label' => 'September 2026',
        ]);
        $projection->assertStatus(201);
        $projectionBody = json_decode((string) $projection->response()->getBody(), true);
        $this->assertSame('proyeksi', $projectionBody['data']['status']);
        $this->assertSame(3, (int) $projectionBody['data']['urutan']);

        // Item terjadwal penuh → status menunggu + pivot unit tersimpan.
        $scheduled = $this->withHeaders($bearer)->withBodyFormat('json')->post('/api/v1/jadwal-banmus/1/item', [
            'agenda'     => 'Rapat Pleno Pembahasan Hasil Banmus',
            'tanggal'    => $tanggal,
            'jam_mulai'  => '09:00',
            'jam_selesai' => '10:00',
            'ruangan_id' => 1,
            'unit_ids'   => [1],
            'publikasi'  => 'publik',
        ]);
        $scheduled->assertStatus(201);
        $scheduledBody = json_decode((string) $scheduled->response()->getBody(), true);
        $this->assertSame('menunggu', $scheduledBody['data']['status']);
        $this->assertSame([1], $scheduledBody['data']['unit_ids']);
        $itemId = (int) $scheduledBody['data']['id'];
        $pivot = $this->apiDb->table('jadwal_banmus_unit_rapat')
            ->where('jadwal_banmus_id', $itemId)->get()->getRowArray();
        $this->assertSame(1, (int) $pivot['unit_rapat_id']);

        // Validasi: jam terbalik, unit tidak valid, konflik ruangan sesama item banmus.
        $this->withHeaders($bearer)->withBodyFormat('json')->post('/api/v1/jadwal-banmus/1/item', [
            'agenda'     => 'Jam terbalik',
            'tanggal'    => $tanggal,
            'jam_mulai'  => '11:00',
            'jam_selesai' => '10:00',
        ])->assertStatus(422);

        $this->withHeaders($bearer)->withBodyFormat('json')->post('/api/v1/jadwal-banmus/1/item', [
            'agenda'     => 'Unit tidak ada',
            'unit_ids'   => [99],
        ])->assertStatus(422);

        $this->withHeaders($bearer)->withBodyFormat('json')->post('/api/v1/jadwal-banmus/1/item', [
            'agenda'     => 'Bentrok ruangan',
            'tanggal'    => $tanggal,
            'jam_mulai'  => '09:30',
            'jam_selesai' => '10:30',
            'ruangan_id' => 1,
            'unit_ids'   => [1],
        ])->assertStatus(422);

        // Show/update item terlingkupi dokumen; doc lain → 404.
        $this->withHeaders($bearer)->get("/api/v1/jadwal-banmus/1/item/{$itemId}")->assertOK();
        $this->withHeaders($bearer)->get('/api/v1/jadwal-banmus/2/item/' . $itemId)->assertStatus(404);

        $updated = $this->withHeaders($bearer)->withBodyFormat('json')
            ->put("/api/v1/jadwal-banmus/1/item/{$itemId}", [
                'agenda'     => 'Rapat Pleno Pembahasan (Revisi)',
                'tanggal'    => $tanggal,
                'jam_mulai'  => '13:00',
                'jam_selesai' => '14:00',
                'ruangan_id' => 1,
                'unit_ids'   => [1],
                'publikasi'  => 'publik',
            ]);
        $updated->assertOK();
        $updatedBody = json_decode((string) $updated->response()->getBody(), true);
        $this->assertSame('Rapat Pleno Pembahasan (Revisi)', $updatedBody['data']['agenda']);
        $this->assertSame('menunggu', $updatedBody['data']['status']);

        // Delete item → soft delete (baris tetap, deleted_at terisi).
        $deleted = $this->withHeaders($bearer)->delete("/api/v1/jadwal-banmus/1/item/{$itemId}");
        $deleted->assertOK();
        $this->assertSame('deleted', json_decode((string) $deleted->response()->getBody(), true)['outcome']);
        $row = $this->apiDb->table('jadwal_banmus')->where('id', $itemId)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
    }

    public function testItemInvitationEndpointsValidateAndClear(): void
    {
        $bearer = ['Authorization' => 'Bearer ' . self::ADMIN_TOKEN];
        $tempPath = (string) tempnam(sys_get_temp_dir(), 'undangan-banmus-');
        file_put_contents($tempPath, '%PDF-1.4 pengujian');

        $this->setFakeUpload($tempPath, 'undangan.pdf', 'application/pdf', 'undangan_file');
        $this->withHeaders($bearer)->post('/api/v1/jadwal-banmus/1/item/999/undangan')->assertStatus(404);
        $this->withHeaders($bearer)->post('/api/v1/jadwal-banmus/999/item/1/undangan')->assertStatus(404);

        $this->setFakeUpload(null, 'undangan.pdf', 'application/pdf', 'undangan_file');
        $missing = $this->withHeaders($bearer)->post('/api/v1/jadwal-banmus/1/item/1/undangan');
        $missing->assertStatus(422);
        $this->assertSame(
            'Berkas undangan wajib diunggah.',
            json_decode((string) $missing->response()->getBody(), true)['message']
        );

        $this->setFakeUpload($tempPath, 'undangan.txt', 'text/plain', 'undangan_file');
        $invalid = $this->withHeaders($bearer)->post('/api/v1/jadwal-banmus/1/item/1/undangan');
        $invalid->assertStatus(422);
        $this->assertSame(
            'Unggahan undangan gagal diproses. Silakan pilih ulang file.',
            json_decode((string) $invalid->response()->getBody(), true)['message']
        );

        $this->apiDb->table('jadwal_banmus')->where('id', 1)->update([
            'undangan_file'      => str_repeat('e', 40) . '.pdf',
            'undangan_nama_asli' => 'undangan-lama.pdf',
        ]);
        $removed = $this->withHeaders($bearer)->delete('/api/v1/jadwal-banmus/1/item/1/undangan');
        $removed->assertOK();
        $this->assertSame('deleted', json_decode((string) $removed->response()->getBody(), true)['outcome']);
        $row = $this->apiDb->table('jadwal_banmus')->where('id', 1)->get()->getRowArray();
        $this->assertNull($row['undangan_file']);
        $this->assertNull($row['undangan_nama_asli']);

        unlink($tempPath);
    }

    public function testServiceReplacesAndRemovesItemInvitation(): void
    {
        $service = new JadwalBanmusService($this->apiDb);
        $storageDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'agenda-invitations';

        $first = $this->documentUploadDouble('undangan-rapat.pdf');
        $this->assertNull($service->replaceItemInvitation(1, $first['file']));

        $row = $this->apiDb->table('jadwal_banmus')->where('id', 1)->get()->getRowArray();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}\.pdf$/', (string) $row['undangan_file']);
        $this->assertSame('undangan-rapat.pdf', $row['undangan_nama_asli']);
        $firstPath = $storageDir . DIRECTORY_SEPARATOR . $row['undangan_file'];
        $this->filesToClean[] = $firstPath;
        $this->assertFileExists($firstPath);

        $second = $this->documentUploadDouble('undangan-rapat-revisi.pdf');
        $this->assertNull($service->replaceItemInvitation(1, $second['file']));

        $row = $this->apiDb->table('jadwal_banmus')->where('id', 1)->get()->getRowArray();
        $secondPath = $storageDir . DIRECTORY_SEPARATOR . $row['undangan_file'];
        $this->filesToClean[] = $secondPath;
        $this->assertFileExists($secondPath);
        $this->assertFileDoesNotExist($firstPath);

        $service->removeItemInvitation(1);
        $row = $this->apiDb->table('jadwal_banmus')->where('id', 1)->get()->getRowArray();
        $this->assertNull($row['undangan_file']);
        $this->assertFileDoesNotExist($secondPath);
    }

    /**
     * Menyuntikkan berkas palsu ke superglobal files — FileCollection CI4
     * membacanya secara lazy saat controller memanggil getFile().
     */
    private function setFakeUpload(
        ?string $tempPath,
        string $clientName = 'dokumen.pdf',
        string $mime = 'application/pdf',
        string $field = 'dokumen_file',
    ): void {
        service('superglobals')->setFilesArray($tempPath === null ? [] : [
            $field => [
                'name'     => $clientName,
                'type'     => $mime,
                'tmp_name' => $tempPath,
                'error'    => UPLOAD_ERR_OK,
                'size'     => (int) filesize($tempPath),
            ],
        ]);
    }

    /**
     * UploadedFile test-double: isValid tanpa is_uploaded_file (berkas
     * lokal) dan move() memakai rename — move_uploaded_file hanya
     * berlaku untuk upload HTTP asli. Pola SettingMediaUploadTest.
     *
     * @return array{file: UploadedFile, path: string}
     */
    private function documentUploadDouble(string $clientName): array
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'sk-dbl-');
        file_put_contents($path, '%PDF-1.4 ' . $clientName);

        $file = new class(
            $path,
            $clientName,
            'application/pdf',
            (int) filesize($path),
            UPLOAD_ERR_OK
        ) extends UploadedFile {
            public function isValid(): bool
            {
                return $this->getError() === UPLOAD_ERR_OK && is_file($this->getTempName());
            }

            public function move(string $targetPath, ?string $name = null, bool $overwrite = false)
            {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0750, true);
                }
                $this->hasMoved = true;

                return rename($this->getTempName(), rtrim($targetPath, '/\\') . DIRECTORY_SEPARATOR . $name);
            }
        };

        return ['file' => $file, 'path' => $path];
    }

    private function seedMasterData(): void
    {
        $this->apiDb->table('ruangan')->insert([
            'name' => 'Ruang Rapat Utama', 'kapasitas' => 50, 'tersedia' => 1,
        ]);
        $this->apiDb->table('unit_rapat')->insertBatch([
            ['nama' => 'Komisi I', 'aktif' => 1, 'urutan' => 1],
            ['nama' => 'Komisi II', 'aktif' => 1, 'urutan' => 2],
        ]);
    }

    /** Menerbitkan access token Shield untuk pengujian. */
    private function issueToken(int $userId, string $rawToken): void
    {
        $this->apiDb->table('auth_identities')->insert([
            'user_id'    => $userId,
            'type'       => 'access_token',
            'name'       => 'test',
            'secret'     => hash('sha256', $rawToken),
            'extra'      => serialize(['*']),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function seedIdentities(): void
    {
        $this->apiDb->table('users')->insert([
            'username' => 'admin-api',
            'name'     => 'Admin API',
            'active'   => 1,
        ]);
        $adminId = (int) $this->apiDb->insertID();
        $this->apiDb->table('auth_groups_users')->insert([
            'user_id' => $adminId, 'group' => 'superadmin', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->issueToken($adminId, self::ADMIN_TOKEN);

        $this->apiDb->table('users')->insert([
            'username' => 'anggota-api',
            'name'     => 'Anggota API',
            'active'   => 1,
        ]);
        $anggotaId = (int) $this->apiDb->insertID();
        $this->apiDb->table('auth_groups_users')->insert([
            'user_id' => $anggotaId, 'group' => 'anggota', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $this->issueToken($anggotaId, self::ANGGOTA_TOKEN);
    }

    private function seedDocuments(): void
    {
        $this->apiDb->table('dokumen_banmus')->insertBatch([
            [
                'judul'             => 'Jadwal Rapat Hasil Banmus Semester 1 Tahun 2026',
                'nomor_sk'          => 'SK/2026/001',
                'tahun'             => 2026,
                'semester'          => 1,
                'status'            => 'disahkan',
                'is_publik'         => 1,
                'dokumen_file'      => str_repeat('c', 40) . '.pdf',
                'dokumen_nama_asli' => 'banmus-2026-1.pdf',
            ],
            [
                'judul'             => 'Jadwal Rapat Hasil Banmus Semester 2 Tahun 2025',
                'nomor_sk'          => 'SK/2025/002',
                'tahun'             => 2025,
                'semester'          => 2,
                'status'            => 'disahkan',
                'is_publik'         => 1,
                'dokumen_file'      => str_repeat('d', 40) . '.pdf',
                'dokumen_nama_asli' => 'banmus-2025-2.pdf',
            ],
        ]);
        $this->apiDb->table('jadwal_banmus')->insertBatch([
            [
                'dokumen_banmus_id' => 1,
                'agenda'            => 'Rapat pleno hasil Banmus I',
                'urutan'            => 1,
                'status'            => 'proyeksi',
            ],
            [
                'dokumen_banmus_id' => 1,
                'agenda'            => 'Rapat komisi pembahasan pokok',
                'urutan'            => 2,
                'status'            => 'proyeksi',
            ],
        ]);
    }

    private function createTables(): void
    {
        $this->apiForge->addField([
            'id'             => ['type' => 'INTEGER', 'auto_increment' => true],
            'username'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status_message' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'active'         => ['type' => 'INTEGER', 'default' => 0],
            'last_active'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('users');

        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'    => ['type' => 'INTEGER'],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_groups_users');

        $this->apiForge->addField([
            'id'           => ['type' => 'INTEGER', 'auto_increment' => true],
            'user_id'      => ['type' => 'INTEGER'],
            'type'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'secret'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'secret2'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'expires'      => ['type' => 'DATETIME', 'null' => true],
            'extra'        => ['type' => 'TEXT', 'null' => true],
            'force_reset'  => ['type' => 'INTEGER', 'default' => 0],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_identities');

        // Shield mencatat percobaan login token (termasuk yang gagal).
        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'id_type'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'identifier' => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_id'    => ['type' => 'INTEGER', 'null' => true],
            'date'       => ['type' => 'DATETIME'],
            'success'    => ['type' => 'INTEGER'],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('auth_token_logins');

        // RequestIdentityService meng-query anggota untuk setiap bearer.
        $this->apiForge->addField([
            'id'            => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fraksi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'komisi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_wa'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif'         => ['type' => 'INTEGER', 'default' => 1],
            'foto'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'user_id'       => ['type' => 'INTEGER', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('anggota');

        $this->apiForge->addField([
            'id'                => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'             => ['type' => 'VARCHAR', 'constraint' => 200],
            'nomor_sk'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'tanggal_sk'        => ['type' => 'DATE', 'null' => true],
            'tahun'             => ['type' => 'INTEGER'],
            'semester'          => ['type' => 'INTEGER'],
            'masa_persidangan'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'periode_mulai'     => ['type' => 'DATE', 'null' => true],
            'periode_selesai'   => ['type' => 'DATE', 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'is_publik'         => ['type' => 'INTEGER', 'default' => 0],
            'dokumen_file'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dokumen_nama_asli' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'dokumen_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'catatan'           => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('dokumen_banmus');

        $this->apiForge->addField([
            'id'                => ['type' => 'INTEGER', 'auto_increment' => true],
            'dokumen_banmus_id' => ['type' => 'INTEGER'],
            'agenda'            => ['type' => 'TEXT'],
            'jenis_agenda'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'rapat'],
            'periode_label'     => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'tanggal_mulai'     => ['type' => 'DATE', 'null' => true],
            'tanggal_selesai'   => ['type' => 'DATE', 'null' => true],
            'teks_tanggal_asli' => ['type' => 'TEXT', 'null' => true],
            'bulan_mulai'       => ['type' => 'CHAR', 'constraint' => 7, 'null' => true],
            'bulan_selesai'     => ['type' => 'CHAR', 'constraint' => 7, 'null' => true],
            'jumlah_pelaksanaan_rencana' => ['type' => 'INTEGER', 'default' => 1],
            'halaman_sumber'    => ['type' => 'INTEGER', 'null' => true],
            'urutan'            => ['type' => 'INTEGER', 'default' => 0],
            'tanggal'           => ['type' => 'DATE', 'null' => true],
            'jam_mulai'         => ['type' => 'TIME', 'null' => true],
            'jam_selesai'       => ['type' => 'TIME', 'null' => true],
            'ruangan_id'        => ['type' => 'INTEGER', 'null' => true],
            'lokasi_lainnya'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'publikasi'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'internal'],
            'materi_url'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'materi_akses'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'peserta'],
            'stream_url'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_akses'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'anggota'],
            'undangan_file'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'undangan_nama_asli'=> ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'proyeksi'],
            'catatan'           => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('jadwal_banmus');

        $this->apiForge->addField([
            'jadwal_banmus_id' => ['type' => 'INTEGER'],
            'unit_rapat_id'    => ['type' => 'INTEGER'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey(['jadwal_banmus_id', 'unit_rapat_id']);
        $this->apiForge->createTable('jadwal_banmus_unit_rapat');

        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'kapasitas'  => ['type' => 'INTEGER', 'default' => 0],
            'tersedia'   => ['type' => 'INTEGER', 'default' => 1],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('ruangan');

        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'aktif'      => ['type' => 'INTEGER', 'default' => 1],
            'urutan'     => ['type' => 'INTEGER', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('unit_rapat');
    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_banmus_unit_rapat', 'jadwal_banmus', 'dokumen_banmus', 'unit_rapat', 'ruangan',
            'anggota', 'auth_token_logins', 'auth_identities', 'auth_groups_users', 'users',
        ] as $table) {
            $this->apiForge->dropTable($table, true);
        }
    }
}
