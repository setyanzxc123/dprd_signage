<?php

use App\Libraries\Crud\JadwalUmumService;
use App\Libraries\Schedule\ScheduleInvitationStorage;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian endpoint API CRUD jadwal umum: otorisasi bearer per grup,
 * paginasi/pencarian + status lifecycle, relasi unit rapat, dan
 * penerusan validasi ke JadwalUmumService.
 *
 * @internal
 */
final class ApiJadwalUmumCrudTest extends CIUnitTestCase
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
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian API jadwal umum.');
        }

        $this->apiDb = Database::connect('tests');
        $this->apiForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
        $this->seedIdentities();
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
        $this->get('/api/v1/jadwal-umum')->assertStatus(401);
        $this
            ->withHeaders(['Authorization' => 'Bearer token-tidak-valid'])
            ->get('/api/v1/jadwal-umum')
            ->assertStatus(401);
    }

    public function testAnggotaTokenIsForbidden(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ANGGOTA_TOKEN])
            ->get('/api/v1/jadwal-umum');

        $response->assertStatus(403);
        $this->assertSame('error', json_decode((string) $response->response()->getBody(), true)['status']);
    }

    public function testListPaginatesSearchesAndComputesStatus(): void
    {
        $response = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/jadwal-umum');

        $response->assertOK();
        $body = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('success', $body['status']);
        $this->assertSame(2, $body['meta']['total']);

        // Terbaru dulu (tanggal DESC) + status lifecycle + nama unit.
        $this->assertSame('Audiensi Luar Kota', $body['data'][0]['judul']);
        $this->assertSame('menunggu', $body['data'][0]['status']);
        $this->assertSame(['Komisi I'], $body['data'][0]['unit_names']);
        $this->assertSame('Kunjungan Organisasi', $body['data'][1]['judul']);
        $this->assertSame([], $body['data'][1]['unit_names']);

        $search = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/jadwal-umum?q=Audiensi');
        $searchBody = json_decode((string) $search->response()->getBody(), true);
        $this->assertSame(1, $searchBody['meta']['total']);
        $this->assertSame('Audiensi Luar Kota', $searchBody['data'][0]['judul']);

        $paged = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/jadwal-umum?per_page=1&page=2');
        $pagedBody = json_decode((string) $paged->response()->getBody(), true);
        $this->assertCount(1, $pagedBody['data']);
        $this->assertSame(2, $pagedBody['meta']['total_pages']);
    }

    public function testCrudFlowViaApi(): void
    {
        $tanggal = $this->futureDate(30);

        $created = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/jadwal-umum', [
                'judul'             => 'Rapat Koordinasi',
                'tanggal'           => $tanggal,
                'waktu_mulai'       => '09:00',
                'waktu_selesai'     => '10:30',
                'lokasi_mode'       => 'ruangan',
                'ruangan_id'        => '1',
                'target_unit_rapat' => ['1'],
                'is_publik'         => '1',
                'keterangan'        => 'Dibuat dari API.',
            ]);

        $created->assertStatus(201);
        $createdBody = json_decode((string) $created->response()->getBody(), true);
        $this->assertSame([1], $createdBody['unit_ids']);
        $this->assertSame('menunggu', $createdBody['data']['status']);
        $id = (int) $createdBody['data']['id'];

        $row = $this->apiDb->table('jadwal_umum')->where('id', $id)->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertSame('09:00:00', $row['waktu_mulai']);
        $this->assertSame(1, (int) $row['ruangan_id']);
        $pivot = $this->apiDb->table('jadwal_umum_unit_rapat')->where('jadwal_umum_id', $id)->get()->getRowArray();
        $this->assertSame(1, (int) $pivot['unit_rapat_id']);

        $this->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/jadwal-umum', ['judul' => ''])
            ->assertStatus(422);

        $updated = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->withBodyFormat('json')
            ->put("/api/v1/jadwal-umum/{$id}", [
                'judul'             => 'Rapat Koordinasi Lanjutan',
                'tanggal'           => $tanggal,
                'waktu_mulai'       => '13:00',
                'waktu_selesai'     => '14:00',
                'lokasi_mode'       => 'ruangan',
                'ruangan_id'        => 1,
                'is_publik'         => '1',
                'target_unit_rapat' => [1],
            ]);
        $updated->assertOK();
        $updatedBody = json_decode((string) $updated->response()->getBody(), true);
        $this->assertSame('Rapat Koordinasi Lanjutan', $updatedBody['data']['judul']);
        $this->assertSame(1, (int) $this->apiDb->table('jadwal_umum')->where('id', $id)->get()->getRowArray()['is_publik']);

        $this->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->get('/api/v1/jadwal-umum/999')
            ->assertStatus(404);

        $deleted = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->delete("/api/v1/jadwal-umum/{$id}");
        $deleted->assertOK();
        $this->assertSame('deleted', json_decode((string) $deleted->response()->getBody(), true)['outcome']);
        $this->assertSame(0, $this->apiDb->table('jadwal_umum')->where('id', $id)->countAllResults());
        $this->assertSame(0, $this->apiDb->table('jadwal_umum_unit_rapat')->where('jadwal_umum_id', $id)->countAllResults());
    }

    public function testRejectsUnitWithoutActiveMembersAndRoomConflict(): void
    {
        $tanggal = $this->futureDate(30);

        // Unit 2 tidak punya anggota aktif.
        $noMembers = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/jadwal-umum', [
                'judul'             => 'Jadwal unit kosong',
                'tanggal'           => $tanggal,
                'waktu_mulai'       => '09:00',
                'waktu_selesai'     => '10:00',
                'lokasi_mode'       => 'ruangan',
                'ruangan_id'        => '1',
                'target_unit_rapat' => ['2'],
            ]);
        $noMembers->assertStatus(422);

        // Ruangan tanpa jam lengkap.
        $incompleteTimes = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/jadwal-umum', [
                'judul'       => 'Jadwal tanpa jam selesai',
                'tanggal'     => $tanggal,
                'waktu_mulai' => '09:00',
                'lokasi_mode' => 'ruangan',
                'ruangan_id'  => '1',
            ]);
        $incompleteTimes->assertStatus(422);

        // Bentrok dengan jadwal existing (hari ini, 09:00-10:00, ruangan 1).
        $conflict = $this
            ->withHeaders(['Authorization' => 'Bearer ' . self::ADMIN_TOKEN])
            ->post('/api/v1/jadwal-umum', [
                'judul'       => 'Jadwal bentrok',
                'tanggal'     => $this->futureDate(31),
                'waktu_mulai' => '09:30',
                'waktu_selesai' => '10:30',
                'lokasi_mode' => 'ruangan',
                'ruangan_id'  => '1',
            ]);
        $conflict->assertStatus(422);
        $this->assertSame(2, $this->apiDb->table('jadwal_umum')->countAllResults());
    }

    public function testInvitationEndpointsValidateUploadAndMissingSchedule(): void
    {
        $bearer = ['Authorization' => 'Bearer ' . self::ADMIN_TOKEN];
        $tempPath = (string) tempnam(sys_get_temp_dir(), 'undangan-api-');
        file_put_contents($tempPath, '%PDF-1.4 pengujian');

        // Jadwal tidak ada → 404 sebelum menyentuh berkas.
        $this->setFakeUpload($tempPath, 'undangan.pdf');
        $this->withHeaders($bearer)->post('/api/v1/jadwal-umum/999/undangan')->assertStatus(404);

        // Berkas tidak disertakan.
        $this->setFakeUpload(null);
        $missingFile = $this->withHeaders($bearer)->post('/api/v1/jadwal-umum/1/undangan');
        $missingFile->assertStatus(422);
        $this->assertSame(
            'Berkas undangan wajib diunggah.',
            json_decode((string) $missingFile->response()->getBody(), true)['message']
        );

        // Unggahan tidak valid ditolak penyimpanan (isValid gagal untuk file
        // yang bukan hasil upload HTTP asli — sama seperti produksi).
        $this->setFakeUpload($tempPath, 'undangan.txt', 'text/plain');
        $invalid = $this->withHeaders($bearer)->post('/api/v1/jadwal-umum/1/undangan');
        $invalid->assertStatus(422);
        $this->assertSame(
            'Unggahan undangan gagal diproses. Silakan pilih ulang file.',
            json_decode((string) $invalid->response()->getBody(), true)['message']
        );
        $this->assertNull(
            $this->apiDb->table('jadwal_umum')->where('id', 1)->get()->getRowArray()['undangan_file']
        );

        // Hapus undangan: 404 untuk jadwal hilang, happy path membersihkan referensi.
        $this->apiDb->table('jadwal_umum')->where('id', 1)->update([
            'undangan_file'      => str_repeat('a', 40) . '.pdf',
            'undangan_nama_asli' => 'undangan-lama.pdf',
        ]);
        $this->withHeaders($bearer)->delete('/api/v1/jadwal-umum/999/undangan')->assertStatus(404);

        $removed = $this->withHeaders($bearer)->delete('/api/v1/jadwal-umum/1/undangan');
        $removed->assertOK();
        $this->assertSame('deleted', json_decode((string) $removed->response()->getBody(), true)['outcome']);
        $row = $this->apiDb->table('jadwal_umum')->where('id', 1)->get()->getRowArray();
        $this->assertNull($row['undangan_file']);
        $this->assertNull($row['undangan_nama_asli']);

        unlink($tempPath);
    }

    public function testReplaceAndRemoveInvitationManageFileLifecycle(): void
    {
        $storageDir = (new ScheduleInvitationStorage())->directory();
        $service = new JadwalUmumService($this->apiDb);

        $first = $this->invitationUploadDouble('rapat-awal.pdf');
        $this->assertNull($service->replaceInvitation(1, $first['file']));

        $row = $this->apiDb->table('jadwal_umum')->where('id', 1)->get()->getRowArray();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}\.pdf$/', (string) $row['undangan_file']);
        $this->assertSame('rapat-awal.pdf', $row['undangan_nama_asli']);
        $firstPath = $storageDir . DIRECTORY_SEPARATOR . $row['undangan_file'];
        $this->filesToClean[] = $firstPath;
        $this->assertFileExists($firstPath);

        // Ganti undangan: berkas lama dihapus setelah yang baru tersimpan.
        $second = $this->invitationUploadDouble('rapat-pengganti.pdf');
        $this->assertNull($service->replaceInvitation(1, $second['file']));

        $row = $this->apiDb->table('jadwal_umum')->where('id', 1)->get()->getRowArray();
        $this->assertSame('rapat-pengganti.pdf', $row['undangan_nama_asli']);
        $secondPath = $storageDir . DIRECTORY_SEPARATOR . $row['undangan_file'];
        $this->filesToClean[] = $secondPath;
        $this->assertNotSame($firstPath, $secondPath);
        $this->assertFileExists($secondPath);
        $this->assertFileDoesNotExist($firstPath);

        $service->removeInvitation(1);
        $row = $this->apiDb->table('jadwal_umum')->where('id', 1)->get()->getRowArray();
        $this->assertNull($row['undangan_file']);
        $this->assertNull($row['undangan_nama_asli']);
        $this->assertFileDoesNotExist($secondPath);
    }

    /**
     * Menyuntikkan berkas palsu ke superglobal files — FileCollection CI4
     * membacanya secara lazy saat controller memanggil getFile().
     */
    private function setFakeUpload(?string $tempPath, string $clientName = 'undangan.pdf', string $mime = 'application/pdf'): void
    {
        service('superglobals')->setFilesArray($tempPath === null ? [] : [
            'undangan_file' => [
                'name'     => $clientName,
                'type'     => $mime,
                'tmp_name' => $tempPath,
                'error'    => UPLOAD_ERR_OK,
                'size'     => (int) filesize($tempPath),
            ],
        ]);
    }

    /**
     * UploadedFile test-double: isValid tanpa is_uploaded_file (berkas lokal)
     * dan move() memakai rename — move_uploaded_file hanya berlaku untuk
     * upload HTTP asli. Pola yang sama dengan SettingMediaUploadTest.
     *
     * @return array{file: UploadedFile, path: string}
     */
    private function invitationUploadDouble(string $clientName): array
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'undangan-dbl-');
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

    /** Tanggal dinamis n hari ke depan — status lifecycle dihitung dari waktu sekarang. */
    private function futureDate(int $days): string
    {
        return date('Y-m-d', strtotime("+{$days} days"));
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

    private function seedMasterData(): void
    {
        $this->apiDb->table('ruangan')->insert([
            'name' => 'Ruang Rapat Utama', 'kapasitas' => 50, 'tersedia' => 1,
        ]);
        $this->apiDb->table('unit_rapat')->insertBatch([
            ['nama' => 'Komisi I', 'aktif' => 1, 'urutan' => 1],
            ['nama' => 'Komisi II', 'aktif' => 1, 'urutan' => 2],
        ]);
        $this->apiDb->table('anggota')->insert([
            'name' => 'Anggota Pengujian', 'aktif' => 1,
        ]);
        $this->apiDb->table('anggota_unit_rapat')->insert([
            'anggota_id' => 1, 'unit_rapat_id' => 1,
        ]);

        // Jadwal existing: satu terhubung Komisi I (+31 hari), satu tanpa unit (+30 hari).
        $this->apiDb->table('jadwal_umum')->insertBatch([
            [
                'judul'         => 'Audiensi Luar Kota',
                'tanggal'       => $this->futureDate(31),
                'waktu_mulai'   => '09:00:00',
                'waktu_selesai' => '10:00:00',
                'ruangan_id'    => 1,
            ],
            [
                'judul'         => 'Kunjungan Organisasi',
                'tanggal'       => $this->futureDate(30),
                'waktu_mulai'   => null,
                'waktu_selesai' => null,
                'ruangan_id'    => null,
            ],
        ]);
        $this->apiDb->table('jadwal_umum_unit_rapat')->insert([
            'jadwal_umum_id' => 1, 'unit_rapat_id' => 1,
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

        $this->apiForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fraksi'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'komisi'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_wa'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif'      => ['type' => 'INTEGER', 'default' => 1],
            'foto'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'user_id'    => ['type' => 'INTEGER', 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('anggota');

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

        $this->apiForge->addField([
            'anggota_id'    => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
        ]);
        $this->apiForge->addPrimaryKey(['anggota_id', 'unit_rapat_id']);
        $this->apiForge->createTable('anggota_unit_rapat');

        $this->apiForge->addField([
            'id'              => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal'         => ['type' => 'DATE'],
            'waktu_mulai'     => ['type' => 'TIME', 'null' => true],
            'waktu_selesai'   => ['type' => 'TIME', 'null' => true],
            'ruangan_id'      => ['type' => 'INTEGER', 'null' => true],
            'lokasi_lainnya'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'pihak_eksternal' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_publik'       => ['type' => 'INTEGER', 'default' => 0],
            'keterangan'      => ['type' => 'TEXT', 'null' => true],
            'materi_url'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'materi_akses'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'stream_url'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'stream_akses'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'undangan_file'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'undangan_nama_asli' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey('id');
        $this->apiForge->createTable('jadwal_umum');

        $this->apiForge->addField([
            'jadwal_umum_id' => ['type' => 'INTEGER'],
            'unit_rapat_id'  => ['type' => 'INTEGER'],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->apiForge->addPrimaryKey(['jadwal_umum_id', 'unit_rapat_id']);
        $this->apiForge->createTable('jadwal_umum_unit_rapat');
    }

    private function dropTables(): void
    {
        foreach ([
            'jadwal_umum_unit_rapat', 'jadwal_umum', 'anggota_unit_rapat', 'unit_rapat',
            'ruangan', 'anggota', 'auth_token_logins', 'auth_identities', 'auth_groups_users', 'users',
        ] as $table) {
            $this->apiForge->dropTable($table, true);
        }
    }
}
