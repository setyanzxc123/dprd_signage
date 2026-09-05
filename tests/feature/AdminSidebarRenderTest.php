<?php

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/**
 * @internal
 */
final class AdminSidebarRenderTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private BaseConnection $sidebarDb;
    private Forge $sidebarForge;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('Ekstensi sqlite3 diperlukan untuk pengujian sidebar admin.');
        }

        $this->sidebarDb    = Database::connect('tests');
        $this->sidebarForge = Database::forge('tests');
        $this->sidebarForge->dropTable('auth_identities', true);
        $this->sidebarForge->dropTable('users', true);
        $this->createShieldUserTables();
        $this->sidebarDb->table('users')->insert([
            'username' => 'admin-test',
            'name'     => 'Admin Pengujian',
            'active'   => 1,
        ]);
    }

    protected function tearDown(): void
    {
        $this->sidebarForge->dropTable('auth_identities', true);
        $this->sidebarForge->dropTable('users', true);

        parent::tearDown();
    }

    public function testSidebarGroupsStayClosedOutsideTheirSection(): void
    {
        $response = $this
            ->withSession([
                'auth_user' => [
                    'id'       => 1,
                    'name'     => 'Admin Pengujian',
                    'username' => 'admin-test',
                    'role'     => 'superadmin',
                ],
            ])
            ->get('/admin/profile');

        $response->assertOK();

        $this->assertStringNotContainsString('group open', $response->response()->getBody());
    }

    private function createShieldUserTables(): void
    {
        $this->sidebarForge->addField([
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
        $this->sidebarForge->addPrimaryKey('id');
        $this->sidebarForge->createTable('users');

        $this->sidebarForge->addField([
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
        $this->sidebarForge->addPrimaryKey('id');
        $this->sidebarForge->createTable('auth_identities');
    }
}
