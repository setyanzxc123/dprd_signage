<?php

use App\Models\AnggotaModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Config as Database;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;

final class AnggotaAuthenticationModelTest extends CIUnitTestCase
{
    private BaseConnection $memberDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->memberDb = Database::connect('tests', false);
            $this->memberDb->initialize();
        } catch (Throwable $exception) {
            $this->markTestSkipped('Database test tidak tersedia: ' . $exception->getMessage());
        }

        $this->forge = Database::forge($this->memberDb);
        $this->forge->dropTable('anggota', true);
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 150],
            'jabatan'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fraksi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'komisi'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_wa'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'foto'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('anggota');
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->forge->dropTable('anggota', true);
        }
        if (isset($this->memberDb)) {
            $this->memberDb->close();
        }

        parent::tearDown();
    }

    public function testUniqueActivePhoneReturnsAnggotaIdentity(): void
    {
        $id = $this->insertMember('Anggota Tunggal', '81234567890');

        $member = (new AnggotaModel($this->memberDb))->findLoginByPhone('81234567890');

        $this->assertNotNull($member);
        $this->assertSame($id, (int) $member['anggota_id']);
        $this->assertArrayNotHasKey('account_id', $member);
    }

    public function testDuplicateActivePhoneIsRejected(): void
    {
        $this->insertMember('Anggota Pertama', '81234567891');
        $this->insertMember('Anggota Kedua', '81234567891');

        $this->assertNull((new AnggotaModel($this->memberDb))->findLoginByPhone('81234567891'));
    }

    public function testLastLoginCanBeUpdatedThroughAnggotaModel(): void
    {
        $id = $this->insertMember('Anggota Login', '81234567892');
        $lastLoginAt = '2026-08-11 21:30:00';

        $this->assertTrue((new AnggotaModel($this->memberDb))->update($id, [
            'last_login_at' => $lastLoginAt,
        ]));

        $row = $this->memberDb->table('anggota')->where('id', $id)->get()->getRowArray();
        $this->assertSame($lastLoginAt, $row['last_login_at']);
    }

    public function testSessionLookupUsesOnlyActiveAnggotaId(): void
    {
        $activeId = $this->insertMember('Anggota Aktif', '81234567893');
        $inactiveId = $this->insertMember('Anggota Nonaktif', '81234567894', 0);
        $model = new AnggotaModel($this->memberDb);

        $member = $model->findActiveSessionMember($activeId);

        $this->assertNotNull($member);
        $this->assertSame($activeId, (int) $member['anggota_id']);
        $this->assertNull($model->findActiveSessionMember($inactiveId));
        $this->assertNull($model->findActiveSessionMember(0));
    }

    private function insertMember(string $name, string $phone, int $active = 1): int
    {
        $this->memberDb->table('anggota')->insert([
            'name'   => $name,
            'no_wa'  => $phone,
            'aktif'  => $active,
        ]);

        return (int) $this->memberDb->insertID();
    }
}
