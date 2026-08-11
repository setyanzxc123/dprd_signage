<?php

use App\Libraries\Otp\OtpStatus;
use App\Libraries\Otp\Persistence\DatabaseOtpRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Config as Database;
use CodeIgniter\Database\Forge;
use CodeIgniter\Test\CIUnitTestCase;

final class DatabaseOtpWebhookRepositoryTest extends CIUnitTestCase
{
    private BaseConnection $otpDb;
    private Forge $forge;
    private DatabaseOtpRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->otpDb = Database::connect('tests', false);
            $this->otpDb->initialize();
        } catch (Throwable $exception) {
            $this->markTestSkipped('Database test tidak tersedia: ' . $exception->getMessage());
        }

        $this->forge = Database::forge($this->otpDb);
        $this->forge->dropTable('member_otps', true);
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'provider' => ['type' => 'VARCHAR', 'constraint' => 32],
            'provider_otp_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'provider_transaction_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 32],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('member_otps');
        $this->repository = new DatabaseOtpRepository($this->otpDb);

        $this->insert('otp-a', 'tx-a');
        $this->insert('otp-b', 'tx-b');
    }

    protected function tearDown(): void
    {
        if (isset($this->forge)) {
            $this->forge->dropTable('member_otps', true);
        }
        if (isset($this->otpDb)) {
            $this->otpDb->close();
        }

        parent::tearDown();
    }

    public function testFindsRowByOtpIdOrTransactionId(): void
    {
        $byOtp = $this->repository->findByProviderIdentifiers('fazpass', 'otp-a', null);
        $byTransaction = $this->repository->findByProviderIdentifiers('fazpass', null, 'tx-a');

        $this->assertNotNull($byOtp);
        $this->assertNotNull($byTransaction);
        $this->assertSame((int) $byOtp['id'], (int) $byTransaction['id']);
    }

    public function testBothIdentifiersMustMatchTheSameRow(): void
    {
        $this->assertNull(
            $this->repository->findByProviderIdentifiers('fazpass', 'otp-a', 'tx-b'),
        );
    }

    public function testAtomicTransitionRejectsStaleSourceStatus(): void
    {
        $otp = $this->repository->findByProviderIdentifiers('fazpass', 'otp-a', 'tx-a');
        $this->assertNotNull($otp);

        $this->assertTrue($this->repository->transitionStatus(
            (int) $otp['id'],
            [OtpStatus::PENDING],
            OtpStatus::DELIVERED,
            ['updated_at' => '2026-08-11 10:01:00'],
        ));
        $this->assertFalse($this->repository->transitionStatus(
            (int) $otp['id'],
            [OtpStatus::PENDING],
            OtpStatus::SENT,
            ['updated_at' => '2026-08-11 10:02:00'],
        ));
    }

    private function insert(string $otpId, string $transactionId): void
    {
        $this->otpDb->table('member_otps')->insert([
            'provider' => 'fazpass',
            'provider_otp_id' => $otpId,
            'provider_transaction_id' => $transactionId,
            'status' => OtpStatus::PENDING,
            'updated_at' => '2026-08-11 10:00:00',
        ]);
    }
}
