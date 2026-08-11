<?php

use App\Database\Migrations\RefactorAndSimplifyDatabase;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Config as Database;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;

require_once APPPATH . 'Database/Migrations/2026-08-11-000001_RefactorAndSimplifyDatabase.php';

final class DatabaseSimplificationMigrationTest extends CIUnitTestCase
{
    private const FINAL_OTP_FIELDS = [
        'id',
        'anggota_id',
        'code_hash',
        'provider',
        'provider_otp_id',
        'provider_transaction_id',
        'status',
        'attempts',
        'expires_at',
        'used_at',
        'created_by_admin_id',
        'created_at',
        'updated_at',
    ];

    private BaseConnection $migrationDb;
    private Forge $forge;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->migrationDb = Database::connect('tests', false);
            $this->migrationDb->initialize();
        } catch (Throwable $exception) {
            $this->markTestSkipped('Database test tidak tersedia: ' . $exception->getMessage());
        }

        $this->forge = Database::forge($this->migrationDb);
        $this->dropTables();
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->dropTables();
        }
        if (isset($this->migrationDb)) {
            $this->migrationDb->close();
        }

        parent::tearDown();
    }

    public function testBuildsFinalOtpSchemaFromFreshParentTables(): void
    {
        $this->createParentTables();

        (new RefactorAndSimplifyDatabase($this->forge))->up();
        $this->migrationDb->resetDataCache();

        $this->assertTrue($this->migrationDb->fieldExists('last_login_at', 'anggota'));
        $this->assertSame(self::FINAL_OTP_FIELDS, $this->migrationDb->getFieldNames('member_otps'));
        $this->assertFinalIndexes();
        $this->assertCount(2, $this->migrationDb->getForeignKeyData('member_otps'));
    }

    public function testReplacesLegacyOtpSchemaAndDropsMergedTables(): void
    {
        $this->createParentTables();
        $this->createLegacyOtpTables();

        (new RefactorAndSimplifyDatabase($this->forge))->up();
        $this->migrationDb->resetDataCache();

        $this->assertFalse($this->migrationDb->tableExists('member_accounts'));
        $this->assertFalse($this->migrationDb->tableExists('member_otp_audits'));
        $this->assertFalse($this->migrationDb->tableExists('otp_webhook_events'));
        $this->assertSame(self::FINAL_OTP_FIELDS, $this->migrationDb->getFieldNames('member_otps'));
        $this->assertSame(0, $this->migrationDb->table('member_otps')->countAllResults());
    }

    public function testRollbackFailsExplicitlyWithResetInstruction(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak dapat di-rollback');

        (new RefactorAndSimplifyDatabase($this->forge))->down();
    }

    private function createParentTables(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('users');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'foto' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('anggota');
    }

    private function createLegacyOtpTables(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'anggota_id' => ['type' => 'INT'],
            'last_login_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('anggota_id', 'anggota', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('member_accounts');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'member_account_id' => ['type' => 'INT'],
            'delivery_status' => ['type' => 'VARCHAR', 'constraint' => 32],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('member_account_id', 'member_accounts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('member_otps');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'member_otp_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('member_otp_id', 'member_otps', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('member_otp_audits');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'event_hash' => ['type' => 'CHAR', 'constraint' => 64],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('otp_webhook_events');
    }

    private function assertFinalIndexes(): void
    {
        $indexes = $this->migrationDb->getIndexData('member_otps');

        foreach ([
            'idx_member_otp_active',
            'idx_member_otp_account_requests',
            'idx_member_otp_global_requests',
            'idx_member_otp_cleanup',
            'uq_member_otp_provider_id',
            'uq_member_otp_transaction_id',
        ] as $index) {
            $this->assertArrayHasKey($index, $indexes);
        }
    }

    private function dropTables(): void
    {
        foreach ([
            'member_otp_audits',
            'otp_webhook_events',
            'member_otps',
            'member_accounts',
            'anggota',
            'users',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
