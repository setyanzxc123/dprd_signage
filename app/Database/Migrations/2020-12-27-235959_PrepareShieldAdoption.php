<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class PrepareShieldAdoption extends Migration
{
    /**
     * Migration runner CI4 mengurutkan migration lintas namespace secara
     * global berdasarkan versi, sehingga migration Shield (2020-12-28)
     * berjalan sebelum seluruh migration App bertimestamp 2026. Agar
     * Shield bisa membuat tabel `users` miliknya, tabel `users` lama
     * wajib dijatuhkan lewat migration bertimestamp lebih awal dari
     * Shield — karena itu file ini memakai timestamp 2020-12-27.
     *
     * Isi tabel users lama diabaikan sesuai rencana adopsi Shield:
     * aplikasi masih tahap pengembangan tanpa data asli, dan akun admin
     * dibuat ulang oleh InitialDataSeeder.
     */
    public function up(): void
    {
        if (! $this->db->tableExists('users')) {
            // Database fresh: tabel users lama belum pernah dibuat.
            return;
        }

        $this->dropForeignKeysReferencingUsers();

        $this->forge->dropTable('users', true);
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Adopsi Shield tidak dapat di-rollback. Jalankan reset database development lalu migrate ulang.',
        );
    }

    /**
     * Menjatuhkan semua foreign key yang merujuk tabel users lama,
     * misalnya member_otps.created_by_admin_id. Kolom pemiliknya
     * dipertahankan sebagai referensi lunak (tanpa FK) karena tabel
     * users milik Shield baru dibuat setelah migration ini berjalan.
     */
    private function dropForeignKeysReferencingUsers(): void
    {
        $constraints = $this->db->query(
            'SELECT TABLE_NAME, CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND REFERENCED_TABLE_NAME = ?
               AND TABLE_NAME != ?',
            ['users', 'users'],
        )->getResultArray();

        foreach ($constraints as $constraint) {
            $this->forge->dropForeignKey(
                $constraint['TABLE_NAME'],
                $constraint['CONSTRAINT_NAME'],
            );
        }
    }
}
