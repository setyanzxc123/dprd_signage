<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UnifyBanmusSchedules extends Migration
{
    public function up(): void
    {
        $this->createJadwalBanmusTable();
        $this->createJadwalBanmusUnitTable();
        $this->createJadwalBanmusDocumentTable();
        $this->migrateLegacyRows();
        $this->migrateLegacyDocuments();

        if ($this->db->tableExists('jadwal')
            && $this->db->fieldExists('proyeksi_banmus_id', 'jadwal')) {
            $this->dropForeignKeysForColumn('jadwal', 'proyeksi_banmus_id');
            $this->forge->dropColumn('jadwal', 'proyeksi_banmus_id');
        }

        if ($this->db->tableExists('proyeksi_banmus_dokumen')) {
            $this->forge->dropTable('proyeksi_banmus_dokumen', true);
        }
        if ($this->db->tableExists('proyeksi_banmus')) {
            $this->forge->dropTable('proyeksi_banmus', true);
        }
    }

    public function down(): void
    {
        $this->createLegacyProjectionTable();
        $this->restoreLegacyRows();
        $this->createLegacyDocumentTable();
        $this->restoreLegacyDocuments();

        if ($this->db->tableExists('jadwal')
            && ! $this->db->fieldExists('proyeksi_banmus_id', 'jadwal')) {
            $this->forge->addColumn('jadwal', [
                'proyeksi_banmus_id' => [
                    'type'       => 'INT',
                    'null'       => true,
                    'default'    => null,
                    'comment'    => 'Sumber proyeksi Banmus',
                    'after'      => 'jenis',
                ],
            ]);
            $this->forge->addForeignKey(
                'proyeksi_banmus_id',
                'proyeksi_banmus',
                'id',
                'SET NULL',
                'CASCADE',
                'fk_jadwal_proyeksi_banmus',
            );
            $this->forge->processIndexes('jadwal');
        }

        $this->forge->dropTable('jadwal_banmus_dokumen', true);
        $this->forge->dropTable('jadwal_banmus_unit_rapat', true);
        $this->forge->dropTable('jadwal_banmus', true);
    }

    private function createJadwalBanmusTable(): void
    {
        if ($this->db->tableExists('jadwal_banmus')) {
            return;
        }

        $this->forge->addField([
            'id'                         => ['type' => 'INT', 'auto_increment' => true],
            'dokumen_banmus_id'          => ['type' => 'INT'],
            'agenda'                     => ['type' => 'TEXT'],
            'periode_label'              => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'default' => null],
            'tanggal_mulai'              => ['type' => 'DATE', 'null' => true, 'default' => null],
            'tanggal_selesai'            => ['type' => 'DATE', 'null' => true, 'default' => null],
            'teks_tanggal_asli'          => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'bulan_mulai'                => ['type' => 'CHAR', 'constraint' => 7, 'null' => true, 'default' => null],
            'bulan_selesai'              => ['type' => 'CHAR', 'constraint' => 7, 'null' => true, 'default' => null],
            'jumlah_pelaksanaan_rencana' => ['type' => 'SMALLINT', 'default' => 1],
            'halaman_sumber'             => ['type' => 'SMALLINT', 'null' => true, 'default' => null],
            'urutan'                     => ['type' => 'INT', 'default' => 0],
            'tanggal'                    => ['type' => 'DATE', 'null' => true, 'default' => null],
            'jam_mulai'                  => ['type' => 'TIME', 'null' => true, 'default' => null],
            'jam_selesai'                => ['type' => 'TIME', 'null' => true, 'default' => null],
            'ruangan_id'                 => ['type' => 'INT', 'null' => true, 'default' => null],
            'lokasi_lainnya'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'publikasi'                  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'internal'],
            'materi_url'                 => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null],
            'stream_url'                 => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null],
            'status'                     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'proyeksi'],
            'catatan'                    => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'created_at'                 => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'                 => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'deleted_at'                 => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['dokumen_banmus_id', 'urutan']);
        $this->forge->addKey(['status', 'tanggal']);
        $this->forge->addKey(['publikasi', 'status', 'tanggal']);
        $this->forge->addKey('ruangan_id');
        $this->forge->addForeignKey('dokumen_banmus_id', 'dokumen_banmus', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ruangan_id', 'ruangan', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('jadwal_banmus', true);
    }

    private function createJadwalBanmusUnitTable(): void
    {
        if ($this->db->tableExists('jadwal_banmus_unit_rapat')) {
            return;
        }

        $this->forge->addField([
            'jadwal_banmus_id' => ['type' => 'INT'],
            'unit_rapat_id'    => ['type' => 'INT'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey(['jadwal_banmus_id', 'unit_rapat_id']);
        $this->forge->addForeignKey('jadwal_banmus_id', 'jadwal_banmus', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('unit_rapat_id', 'unit_rapat', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('jadwal_banmus_unit_rapat', true);
    }

    private function createJadwalBanmusDocumentTable(): void
    {
        if ($this->db->tableExists('jadwal_banmus_dokumen')) {
            return;
        }

        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'jadwal_banmus_id' => ['type' => 'INT'],
            'jenis'             => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'lampiran'],
            'nama'              => ['type' => 'VARCHAR', 'constraint' => 200],
            'file_path'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'url'               => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null],
            'is_publik'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'urutan'            => ['type' => 'INT', 'default' => 0],
            'created_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['jadwal_banmus_id', 'urutan']);
        $this->forge->addForeignKey('jadwal_banmus_id', 'jadwal_banmus', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('jadwal_banmus_dokumen', true);
    }

    private function migrateLegacyRows(): void
    {
        if (! $this->db->tableExists('proyeksi_banmus')) {
            return;
        }

        $rows = $this->db->table('proyeksi_banmus')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if ($this->db->table('jadwal_banmus')->where('id', $id)->countAllResults() === 0) {
                $status = (string) ($row['status'] ?? 'proyeksi');
                if (! in_array($status, ['proyeksi', 'fixed', 'selesai', 'ditunda', 'dibatalkan'], true)) {
                    $status = 'proyeksi';
                }

                $this->db->table('jadwal_banmus')->insert([
                    'id'                         => $id,
                    'dokumen_banmus_id'          => (int) $row['dokumen_banmus_id'],
                    'agenda'                     => (string) ($row['agenda'] ?? ''),
                    'periode_label'              => $this->nullableString($row['periode_label'] ?? null),
                    'tanggal_mulai'              => $row['tanggal_mulai'] ?? null,
                    'tanggal_selesai'            => $row['tanggal_selesai'] ?? null,
                    'teks_tanggal_asli'          => $this->nullableString($row['teks_tanggal_asli'] ?? null),
                    'bulan_mulai'                => $this->nullableString($row['bulan_mulai'] ?? null),
                    'bulan_selesai'              => $this->nullableString($row['bulan_selesai'] ?? null),
                    'jumlah_pelaksanaan_rencana' => max(1, (int) ($row['jumlah_pelaksanaan_rencana'] ?? 1)),
                    'halaman_sumber'             => isset($row['halaman_sumber']) ? (int) $row['halaman_sumber'] : null,
                    'urutan'                     => (int) ($row['urutan'] ?? 0),
                    'tanggal'                    => $row['tanggal'] ?? null,
                    'jam_mulai'                  => $row['jam_mulai'] ?? null,
                    'jam_selesai'                => $row['jam_selesai'] ?? null,
                    'ruangan_id'                 => isset($row['ruangan_id']) ? (int) $row['ruangan_id'] : null,
                    'lokasi_lainnya'             => $this->nullableString($row['lokasi_lainnya'] ?? null),
                    'publikasi'                  => in_array(($row['publikasi'] ?? null), ['internal', 'publik'], true)
                        ? $row['publikasi']
                        : 'internal',
                    'materi_url'                 => $this->nullableString($row['materi_url'] ?? null),
                    'stream_url'                 => $this->nullableString($row['stream_url'] ?? null),
                    'status'                     => $status,
                    'catatan'                    => $this->nullableString($row['catatan'] ?? null),
                    'created_at'                 => $row['created_at'] ?? null,
                    'updated_at'                 => $row['updated_at'] ?? null,
                ]);
            }

            $unitIds = [];
            if (! empty($row['unit_rapat_id'])) {
                $unitIds[] = (int) $row['unit_rapat_id'];
            }
            $jsonIds = json_decode((string) ($row['target_unit_ids'] ?? ''), true);
            if (is_array($jsonIds)) {
                $unitIds = array_merge($unitIds, array_map('intval', $jsonIds));
            }

            foreach (array_values(array_unique(array_filter($unitIds))) as $unitId) {
                if ($this->db->table('unit_rapat')->where('id', $unitId)->countAllResults() === 0) {
                    continue;
                }
                if ($this->db->table('jadwal_banmus_unit_rapat')
                    ->where('jadwal_banmus_id', $id)
                    ->where('unit_rapat_id', $unitId)
                    ->countAllResults() > 0) {
                    continue;
                }

                $this->db->table('jadwal_banmus_unit_rapat')->insert([
                    'jadwal_banmus_id' => $id,
                    'unit_rapat_id'    => $unitId,
                    'created_at'       => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function createLegacyProjectionTable(): void
    {
        if ($this->db->tableExists('proyeksi_banmus')) {
            return;
        }

        $this->forge->addField([
            'id'                         => ['type' => 'INT', 'auto_increment' => true],
            'dokumen_banmus_id'          => ['type' => 'INT'],
            'agenda'                     => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'periode_label'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null],
            'tanggal_mulai'              => ['type' => 'DATE', 'null' => true, 'default' => null],
            'tanggal_selesai'            => ['type' => 'DATE', 'null' => true, 'default' => null],
            'unit_rapat_id'              => ['type' => 'INT', 'null' => true, 'default' => null],
            'urutan'                     => ['type' => 'INT', 'default' => 0],
            'status'                     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'proyeksi'],
            'catatan'                    => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'jadwal_id'                  => ['type' => 'INT', 'null' => true, 'default' => null],
            'tanggal'                    => ['type' => 'DATE', 'null' => true, 'default' => null],
            'jam_mulai'                  => ['type' => 'TIME', 'null' => true, 'default' => null],
            'jam_selesai'                => ['type' => 'TIME', 'null' => true, 'default' => null],
            'ruangan_id'                 => ['type' => 'INT', 'null' => true, 'default' => null],
            'lokasi_lainnya'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'target_unit_ids'            => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'publikasi'                  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'internal'],
            'kepastian_tanggal'          => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'belum_ditentukan'],
            'teks_tanggal_asli'          => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'bulan_mulai'                => ['type' => 'CHAR', 'constraint' => 7, 'null' => true, 'default' => null],
            'bulan_selesai'              => ['type' => 'CHAR', 'constraint' => 7, 'null' => true, 'default' => null],
            'jumlah_pelaksanaan_rencana' => ['type' => 'SMALLINT', 'default' => 1],
            'halaman_sumber'             => ['type' => 'SMALLINT', 'null' => true, 'default' => null],
            'created_at'                 => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'                 => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['dokumen_banmus_id', 'urutan']);
        $this->forge->addForeignKey('dokumen_banmus_id', 'dokumen_banmus', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('proyeksi_banmus', true);
    }

    private function createLegacyDocumentTable(): void
    {
        if ($this->db->tableExists('proyeksi_banmus_dokumen')) {
            return;
        }

        $this->forge->addField([
            'id'                   => ['type' => 'INT', 'auto_increment' => true],
            'proyeksi_banmus_id'  => ['type' => 'INT'],
            'jenis'                => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'lampiran'],
            'nama'                 => ['type' => 'VARCHAR', 'constraint' => 200],
            'file_path'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'url'                  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null],
            'is_publik'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'urutan'               => ['type' => 'INT', 'default' => 0],
            'created_at'           => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['proyeksi_banmus_id', 'urutan']);
        $this->forge->addForeignKey('proyeksi_banmus_id', 'proyeksi_banmus', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('proyeksi_banmus_dokumen', true);
    }

    private function migrateLegacyDocuments(): void
    {
        if (! $this->db->tableExists('proyeksi_banmus_dokumen')) {
            return;
        }

        foreach ($this->db->table('proyeksi_banmus_dokumen')->get()->getResultArray() as $row) {
            if ($this->db->table('jadwal_banmus_dokumen')->where('id', (int) $row['id'])->countAllResults() > 0) {
                continue;
            }

            $this->db->table('jadwal_banmus_dokumen')->insert([
                'id'                => (int) $row['id'],
                'jadwal_banmus_id' => (int) $row['proyeksi_banmus_id'],
                'jenis'             => $row['jenis'],
                'nama'              => $row['nama'],
                'file_path'         => $row['file_path'],
                'url'               => $row['url'],
                'is_publik'         => $row['is_publik'],
                'urutan'            => $row['urutan'],
                'created_at'        => $row['created_at'],
                'updated_at'        => $row['updated_at'],
            ]);
        }
    }

    private function restoreLegacyRows(): void
    {
        if (! $this->db->tableExists('jadwal_banmus')
            || ! $this->db->tableExists('proyeksi_banmus')) {
            return;
        }

        $unitRows = $this->db->table('jadwal_banmus_unit_rapat')->get()->getResultArray();
        $unitMap = [];
        foreach ($unitRows as $unitRow) {
            $unitMap[(int) $unitRow['jadwal_banmus_id']][] = (int) $unitRow['unit_rapat_id'];
        }

        foreach ($this->db->table('jadwal_banmus')->get()->getResultArray() as $row) {
            $id = (int) $row['id'];
            if ($this->db->table('proyeksi_banmus')->where('id', $id)->countAllResults() > 0) {
                continue;
            }

            $this->db->table('proyeksi_banmus')->insert([
                'id'                         => $id,
                'dokumen_banmus_id'          => (int) $row['dokumen_banmus_id'],
                'agenda'                     => $row['agenda'],
                'periode_label'              => $row['periode_label'],
                'tanggal_mulai'              => $row['tanggal_mulai'],
                'tanggal_selesai'            => $row['tanggal_selesai'],
                'urutan'                     => $row['urutan'],
                'status'                     => $row['status'],
                'catatan'                    => $row['catatan'],
                'tanggal'                    => $row['tanggal'],
                'jam_mulai'                  => $row['jam_mulai'],
                'jam_selesai'                => $row['jam_selesai'],
                'ruangan_id'                 => $row['ruangan_id'],
                'lokasi_lainnya'             => $row['lokasi_lainnya'],
                'target_unit_ids'            => isset($unitMap[$id]) ? json_encode($unitMap[$id]) : null,
                'publikasi'                  => $row['publikasi'],
                'kepastian_tanggal'          => $row['status'] === 'fixed' ? 'tanggal_pasti' : 'belum_ditentukan',
                'teks_tanggal_asli'          => $row['teks_tanggal_asli'],
                'bulan_mulai'                => $row['bulan_mulai'],
                'bulan_selesai'              => $row['bulan_selesai'],
                'jumlah_pelaksanaan_rencana' => $row['jumlah_pelaksanaan_rencana'],
                'halaman_sumber'             => $row['halaman_sumber'],
                'created_at'                 => $row['created_at'],
                'updated_at'                 => $row['updated_at'],
            ]);
        }
    }

    private function restoreLegacyDocuments(): void
    {
        if (! $this->db->tableExists('jadwal_banmus_dokumen')
            || ! $this->db->tableExists('proyeksi_banmus_dokumen')) {
            return;
        }

        foreach ($this->db->table('jadwal_banmus_dokumen')->get()->getResultArray() as $row) {
            if ($this->db->table('proyeksi_banmus_dokumen')->where('id', (int) $row['id'])->countAllResults() > 0) {
                continue;
            }

            $this->db->table('proyeksi_banmus_dokumen')->insert([
                'id'                  => (int) $row['id'],
                'proyeksi_banmus_id' => (int) $row['jadwal_banmus_id'],
                'jenis'               => $row['jenis'],
                'nama'                => $row['nama'],
                'file_path'           => $row['file_path'],
                'url'                 => $row['url'],
                'is_publik'           => $row['is_publik'],
                'urutan'              => $row['urutan'],
                'created_at'          => $row['created_at'],
                'updated_at'          => $row['updated_at'],
            ]);
        }
    }

    private function dropForeignKeysForColumn(string $table, string $column): void
    {
        $constraints = [];
        foreach ($this->db->getForeignKeyData($table) as $foreignKey) {
            $data = array_change_key_case((array) $foreignKey, CASE_LOWER);
            $foreignColumn = $data['column_name']
                ?? $data['columnname']
                ?? null;
            $constraint = $data['constraint_name']
                ?? $data['constraintname']
                ?? null;

            if ($foreignColumn === $column && is_string($constraint) && $constraint !== '') {
                $constraints[] = $constraint;
            }
        }

        if ($this->db->DBDriver === 'MySQLi') {
            $rows = $this->db->query(
                'SELECT CONSTRAINT_NAME
                 FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND COLUMN_NAME = ?
                   AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$table, $column],
            )->getResultArray();
            $constraints = array_merge($constraints, array_column($rows, 'CONSTRAINT_NAME'));
        }

        foreach (array_values(array_unique($constraints)) as $constraint) {
            $this->forge->dropForeignKey($table, (string) $constraint);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
