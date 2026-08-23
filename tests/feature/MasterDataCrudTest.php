<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * Pengujian CRUD master data (ruangan, unit rapat, anggota) setelah
 * logikanya diekstraksi ke App\Libraries\Crud.
 *
 * @internal
 */
final class MasterDataCrudTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $masterDb;
    private Forge $masterForge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian CRUD master data.');
        }

        $this->masterDb = Database::connect('tests');
        $this->masterForge = Database::forge('tests');
        $this->dropTables();
        $this->createTables();
    }

    protected function tearDown(): void
    {
        if (isset($this->masterForge)) {
            $this->dropTables();
        }

        parent::tearDown();
    }

    public function testRoomCrudValidatesAndPersists(): void
    {
        $this->adminPost('/admin/ruangan/store', [
            'name'      => '',
            'kapasitas' => '10',
        ])->assertStatus(422);

        $this->adminPost('/admin/ruangan/store', [
            'name'      => 'Ruang Rapat Utama',
            'keterangan' => 'Lantai 2',
            'kapasitas' => '50',
            'tersedia'  => '1',
        ])->assertStatus(303);

        $room = $this->masterDb->table('ruangan')->get()->getRowArray();
        $this->assertSame('Ruang Rapat Utama', $room['name']);
        $this->assertSame(50, (int) $room['kapasitas']);

        // Tanpa relasi jadwal, ruangan dihapus fisik.
        $this->adminPost('/admin/ruangan/' . $room['id'] . '/delete', [])->assertStatus(302);
        $this->assertSame(0, $this->masterDb->table('ruangan')->countAllResults());
    }

    public function testRoomWithScheduleHistoryIsDeactivatedInsteadOfDeleted(): void
    {
        $this->masterDb->table('ruangan')->insert(['name' => 'Ruang Sibuk', 'kapasitas' => 10, 'tersedia' => 1]);
        $this->masterDb->table('jadwal_umum')->insert([
            'judul'    => 'Riwayat rapat',
            'tanggal'  => '2099-01-01',
            'ruangan_id' => 1,
        ]);

        $this->adminPost('/admin/ruangan/1/delete', [])->assertStatus(302);

        $room = $this->masterDb->table('ruangan')->where('id', 1)->get()->getRowArray();
        $this->assertNotNull($room);
        $this->assertSame(0, (int) $room['tersedia']);
    }

    public function testUnitRapatRequiresMemberWhenActiveAndSyncsMembership(): void
    {
        $this->masterDb->table('anggota')->insert([
            'name' => 'Anggota Pengujian',
            'no_wa' => '81234567890',
            'aktif' => 1,
        ]);

        $this->adminPost('/admin/unit-rapat/store', [
            'nama'  => 'Komisi I',
            'aktif' => '1',
        ])->assertStatus(422);

        $this->adminPost('/admin/unit-rapat/store', [
            'nama'              => 'Komisi I',
            'aktif'             => '1',
            'anggota_unit_rapat' => ['1'],
        ])->assertStatus(303);

        $unit = $this->masterDb->table('unit_rapat')->get()->getRowArray();
        $this->assertSame('Komisi I', $unit['nama']);

        $pivot = $this->masterDb->table('anggota_unit_rapat')->get()->getRowArray();
        $this->assertSame(1, (int) $pivot['anggota_id']);
        $this->assertSame((int) $unit['id'], (int) $pivot['unit_rapat_id']);

        $this->adminPost('/admin/unit-rapat/' . $unit['id'] . '/delete', [])->assertStatus(302);
        $this->assertSame(0, (int) $this->masterDb->table('unit_rapat')->where('id', $unit['id'])->get()->getRowArray()['aktif']);
    }

    public function testMemberCrudValidatesFraksiAndUniquePhone(): void
    {
        $this->adminPost('/admin/anggota/store', [
            'name'   => 'Anggota Satu',
            'fraksi' => 'Fraksi Fiktif',
            'no_wa'  => '81234567890',
        ])->assertStatus(422);

        $this->adminPost('/admin/anggota/store', [
            'name'   => 'Anggota Satu',
            'fraksi' => 'PDIP',
            'komisi' => 'Komisi I',
            'no_wa'  => '0812-3456-7890',
        ])->assertStatus(303);

        $member = $this->masterDb->table('anggota')->get()->getRowArray();
        $this->assertSame('Anggota Satu', $member['name']);
        $this->assertSame('81234567890', $member['no_wa']);

        $this->adminPost('/admin/anggota/store', [
            'name'   => 'Anggota Dua',
            'fraksi' => 'Demokrat',
            'no_wa'  => '6281234567890',
        ])->assertStatus(422);

        // Anggota tanpa relasi dihapus fisik; berrelasi dinonaktifkan.
        $this->adminPost('/admin/anggota/' . $member['id'] . '/delete', [])->assertStatus(302);
        $this->assertSame(0, $this->masterDb->table('anggota')->countAllResults());
    }

    public function testMemberWithUnitRelationIsDeactivatedInsteadOfDeleted(): void
    {
        $this->masterDb->table('anggota')->insert(['name' => 'Anggota Terkait', 'no_wa' => '81234567891', 'aktif' => 1]);
        $this->masterDb->table('unit_rapat')->insert(['nama' => 'Komisi II', 'aktif' => 1]);
        $this->masterDb->table('anggota_unit_rapat')->insert(['anggota_id' => 1, 'unit_rapat_id' => 1]);

        $this->adminPost('/admin/anggota/1/delete', [])->assertStatus(302);

        $member = $this->masterDb->table('anggota')->where('id', 1)->get()->getRowArray();
        $this->assertNotNull($member);
        $this->assertSame(0, (int) $member['aktif']);
    }

    private function adminPost(string $path, array $payload)
    {
        return $this->withSession(['auth_user' => $this->adminSession()])->post($path, [
            csrf_token() => csrf_hash(),
            ...$payload,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSession(): array
    {
        return [
            'id'       => 1,
            'name'     => 'Admin Pengujian',
            'username' => 'admin-test',
            'role'     => 'superadmin',
        ];
    }

    private function createTables(): void
    {
        $this->masterForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'kapasitas'  => ['type' => 'INTEGER', 'default' => 0],
            'tersedia'   => ['type' => 'INTEGER', 'default' => 1],
        ]);
        $this->masterForge->addPrimaryKey('id');
        $this->masterForge->createTable('ruangan');

        $this->masterForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'aktif'      => ['type' => 'INTEGER', 'default' => 1],
            'urutan'     => ['type' => 'INTEGER', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->masterForge->addPrimaryKey('id');
        $this->masterForge->createTable('unit_rapat');

        $this->masterForge->addField([
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
        $this->masterForge->addPrimaryKey('id');
        $this->masterForge->createTable('anggota');

        $this->masterForge->addField([
            'anggota_id'    => ['type' => 'INTEGER'],
            'unit_rapat_id' => ['type' => 'INTEGER'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->masterForge->addPrimaryKey(['anggota_id', 'unit_rapat_id']);
        $this->masterForge->createTable('anggota_unit_rapat');

        $this->masterForge->addField([
            'id'         => ['type' => 'INTEGER', 'auto_increment' => true],
            'judul'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal'    => ['type' => 'DATE'],
            'ruangan_id' => ['type' => 'INTEGER', 'null' => true],
        ]);
        $this->masterForge->addPrimaryKey('id');
        $this->masterForge->createTable('jadwal_umum');
    }

    private function dropTables(): void
    {
        foreach (['jadwal_umum', 'anggota_unit_rapat', 'anggota', 'unit_rapat', 'ruangan'] as $table) {
            $this->masterForge->dropTable($table, true);
        }
    }
}
