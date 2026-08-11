<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RefactorAndSimplifyDatabase extends Migration
{
    public function up(): void
    {
        // Seluruh data pada tahap ini masih dummy. Migration sengaja membentuk
        // schema final tanpa mempertahankan row dari tabel yang disederhanakan.
        if (! $this->db->tableExists('anggota') || ! $this->db->tableExists('users')) {
            throw new \RuntimeException('Tabel induk anggota dan users wajib tersedia sebelum simplifikasi database.');
        }

        if ($this->db->tableExists('anggota') && ! $this->db->fieldExists('last_login_at', 'anggota')) {
            $this->forge->addColumn('anggota', [
                'last_login_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'foto'],
            ]);
        }

        // Anak harus dihapus sebelum tabel induk legacy agar urutan foreign key valid.
        foreach ([
            'member_otp_audits',
            'otp_webhook_events',
            'notifikasi',
            'jadwal_banmus_dokumen',
            'agenda_audit_log',
            'agenda_migration_state',
            'jadwal_umum_legacy_map',
            'member_otps',
            'member_accounts',
        ] as $table) {
            if ($this->db->tableExists($table)) {
                $this->forge->dropTable($table, true);
            }
        }

        $this->forge->addField([
            'id'                      => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'anggota_id'              => ['type' => 'INT', 'null' => false],
            'code_hash'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'provider'                => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'fazpass'],
            'provider_otp_id'         => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'provider_transaction_id' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'status'                  => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'created'],
            'attempts'                => ['type' => 'SMALLINT', 'default' => 0],
            'expires_at'              => ['type' => 'DATETIME'],
            'used_at'                 => ['type' => 'DATETIME', 'null' => true],
            'created_by_admin_id'     => ['type' => 'INT', 'null' => true],
            'created_at'              => ['type' => 'DATETIME'],
            'updated_at'              => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('anggota_id', 'anggota', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by_admin_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->addKey(
            ['anggota_id', 'status', 'used_at', 'expires_at'],
            false,
            false,
            'idx_member_otp_active',
        );
        $this->forge->addKey(
            ['anggota_id', 'provider', 'created_at'],
            false,
            false,
            'idx_member_otp_account_requests',
        );
        $this->forge->addKey(
            ['provider', 'created_at'],
            false,
            false,
            'idx_member_otp_global_requests',
        );
        $this->forge->addKey('expires_at', false, false, 'idx_member_otp_cleanup');
        $this->forge->addUniqueKey(['provider', 'provider_otp_id'], 'uq_member_otp_provider_id');
        $this->forge->addUniqueKey(['provider', 'provider_transaction_id'], 'uq_member_otp_transaction_id');
        $this->forge->createTable('member_otps', true);
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Migration simplifikasi database tidak dapat di-rollback. Jalankan reset database development lalu migrate ulang.',
        );
    }
}
