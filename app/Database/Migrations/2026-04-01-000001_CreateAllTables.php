<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAllTables extends Migration
{
    public function up(): void
    {
        // ── 1. Tabel users (admin) ─────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'username'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null],
            'password'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'role'       => ['type' => 'ENUM', 'constraint' => ['superadmin', 'operator'], 'default' => 'operator'],
            'created_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users', true);

        // ── 2. Tabel anggota DPRD ──────────────────────────────────────
        $this->forge->addField([
            'id'      => ['type' => 'INT', 'auto_increment' => true],
            'name'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'jabatan' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fraksi'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'komisi'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'no_wa'   => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'aktif'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'foto'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('anggota', true);

        // ── 3. Tabel ruangan rapat ─────────────────────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'kapasitas'  => ['type' => 'INT', 'default' => 0],
            'lantai'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'tersedia'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('ruangan', true);

        // ── 4. Tabel jadwal rapat ──────────────────────────────────────
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'judul'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'keterangan'    => ['type' => 'TEXT', 'null' => true],
            'tanggal'       => ['type' => 'DATE', 'null' => false],
            'waktu_mulai'   => ['type' => 'TIME', 'null' => false],
            'waktu_selesai' => ['type' => 'TIME', 'null' => false],
            'ruangan_id'    => ['type' => 'INT', 'null' => true],
            'komisi_target' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'blast_before'  => ['type' => 'INT', 'default' => 60],
            'reminder_time' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'status'        => [
                'type'       => 'ENUM',
                'constraint' => ['menunggu', 'persiapan', 'berlangsung', 'selesai'],
                'default'    => 'menunggu',
            ],
            'materi_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('ruangan_id', 'ruangan', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('jadwal', true);

        // ── 5. Tabel log notifikasi WA ─────────────────────────────────
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'auto_increment' => true],
            'jadwal_id'   => ['type' => 'INT', 'null' => true],
            'anggota_id'  => ['type' => 'INT', 'null' => true],
            'no_wa'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'status'      => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'sent', 'failed'],
                'default'    => 'pending',
            ],
            'executed_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'created_at'  => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('jadwal_id', 'jadwal', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('anggota_id', 'anggota', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('notifikasi', true);

        // ── 6. Tabel settings (key-value) ─────────────────────────────
        $this->forge->addField([
            'key_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'value'    => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('key_name');
        $this->forge->createTable('settings', true);
    }

    public function down(): void
    {
        // Hapus dalam urutan terbalik (karena foreign key)
        $this->forge->dropTable('notifikasi', true);
        $this->forge->dropTable('jadwal',     true);
        $this->forge->dropTable('ruangan',    true);
        $this->forge->dropTable('anggota',    true);
        $this->forge->dropTable('settings',   true);
        $this->forge->dropTable('users',      true);
    }
}
