<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AdoptShieldIdentity extends Migration
{
    /**
     * Berjalan setelah migration Shield membuat tabel auth (timestamp
     * Shield 2020-12-28 < 2026-08-23 pada urutan global), sehingga aman
     * mengubah schema tabel milik Shield.
     */
    public function up(): void
    {
        if (! $this->db->fieldExists('name', 'users')) {
            $this->forge->addColumn('users', [
                'name' => [
                    'type'     => 'VARCHAR',
                    'constraint' => 100,
                    'null'     => true,
                    'after'    => 'username',
                ],
            ]);
        }

        if (! $this->db->fieldExists('user_id', 'anggota')) {
            $this->forge->addColumn('anggota', [
                'user_id' => [
                    'type'     => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null'     => true,
                    'after'    => 'aktif',
                ],
            ]);
        }

        if (! $this->indexExists('anggota', 'idx_anggota_user_id')) {
            $this->db->query('ALTER TABLE anggota ADD INDEX idx_anggota_user_id (user_id)');
        }
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Adopsi Shield tidak dapat di-rollback. Jalankan reset database development lalu migrate ulang.',
        );
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return $this->db
            ->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName])
            ->getResultArray() !== [];
    }
}
