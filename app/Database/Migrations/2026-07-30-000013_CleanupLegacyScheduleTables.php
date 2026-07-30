<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class CleanupLegacyScheduleTables extends Migration
{
    private const MAP_TABLE = 'jadwal_umum_legacy_map';

    public function up(): void
    {
        $this->assertLegacyRowsWereMigrated('jadwal');
        $this->assertLegacyRowsWereMigrated('agenda_umum');

        $this->forge->dropTable('jadwal_unit_rapat', true);
        $this->forge->dropTable('jadwal', true);
        $this->forge->dropTable('agenda_umum', true);
    }

    public function down(): void
    {
        $this->createLegacyScheduleTable();
        $this->createLegacyScheduleUnitTable();
        $this->createLegacyGeneralAgendaTable();

        if (! $this->db->tableExists(self::MAP_TABLE)
            || ! $this->db->tableExists('jadwal_umum')) {
            return;
        }

        $this->restoreLegacySchedules();
        $this->restoreLegacyGeneralAgendas();
    }

    private function assertLegacyRowsWereMigrated(string $sourceTable): void
    {
        if (! $this->db->tableExists($sourceTable)) {
            return;
        }

        $legacyIds = array_map(
            'intval',
            array_column(
                $this->db->table($sourceTable)->select('id')->get()->getResultArray(),
                'id',
            ),
        );
        if ($legacyIds === []) {
            return;
        }
        if (! $this->db->tableExists(self::MAP_TABLE)) {
            throw new RuntimeException(
                "Cleanup dibatalkan: mapping migrasi {$sourceTable} tidak tersedia.",
            );
        }

        foreach ($legacyIds as $legacyId) {
            $mapping = $this->db->table(self::MAP_TABLE . ' map')
                ->select('map.jadwal_umum_id')
                ->join('jadwal_umum ju', 'ju.id = map.jadwal_umum_id')
                ->where('map.source_table', $sourceTable)
                ->where('map.legacy_id', $legacyId)
                ->get(1)
                ->getRowArray();
            if ($mapping === null) {
                throw new RuntimeException(
                    "Cleanup dibatalkan: {$sourceTable}.{$legacyId} belum termigrasi.",
                );
            }
        }
    }

    private function createLegacyScheduleTable(): void
    {
        if ($this->db->tableExists('jadwal')) {
            return;
        }

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'auto_increment' => true],
            'judul'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'keterangan'       => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'tanggal'          => ['type' => 'DATE'],
            'waktu_mulai'      => ['type' => 'TIME'],
            'waktu_selesai'    => ['type' => 'TIME'],
            'ruangan_id'       => ['type' => 'INT', 'null' => true, 'default' => null],
            'lokasi_lainnya'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'menunggu'],
            'materi_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null],
            'materi_akses'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'peserta'],
            'jenis'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'insidental'],
            'is_publik'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'stream_url'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'default' => null],
            'stream_akses'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'anggota'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('jadwal', true);
    }

    private function createLegacyScheduleUnitTable(): void
    {
        if ($this->db->tableExists('jadwal_unit_rapat')) {
            return;
        }

        $this->forge->addField([
            'jadwal_id'     => ['type' => 'INT'],
            'unit_rapat_id' => ['type' => 'INT'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey(['jadwal_id', 'unit_rapat_id'], true);
        $this->forge->createTable('jadwal_unit_rapat', true);
    }

    private function createLegacyGeneralAgendaTable(): void
    {
        if ($this->db->tableExists('agenda_umum')) {
            return;
        }

        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'judul'             => ['type' => 'VARCHAR', 'constraint' => 200],
            'kategori'          => ['type' => 'VARCHAR', 'constraint' => 30],
            'pihak_eksternal'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'tanggal'           => ['type' => 'DATE'],
            'waktu_mulai'       => ['type' => 'TIME'],
            'waktu_selesai'     => ['type' => 'TIME', 'null' => true, 'default' => null],
            'lokasi'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'sumber_informasi'  => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'default' => null],
            'keterangan'        => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'is_publik'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('agenda_umum', true);
    }

    private function restoreLegacySchedules(): void
    {
        foreach ($this->mappedGeneralSchedules('jadwal') as $row) {
            $start = $this->timeOrDefault($row['waktu_mulai'] ?? null);
            $end = $this->timeOrDefault($row['waktu_selesai'] ?? null, $start);
            $this->db->table('jadwal')->ignore(true)->insert([
                'id'               => (int) $row['legacy_id'],
                'judul'            => $row['judul'],
                'keterangan'       => $row['keterangan'],
                'tanggal'          => $row['tanggal'],
                'waktu_mulai'      => $start,
                'waktu_selesai'    => $end,
                'ruangan_id'       => $row['ruangan_id'],
                'lokasi_lainnya'   => $row['lokasi_lainnya'],
                'status'           => 'menunggu',
                'jenis'            => 'insidental',
                'is_publik'        => (int) $row['is_publik'],
            ]);

            if (! $this->db->tableExists('jadwal_umum_unit_rapat')) {
                continue;
            }
            $units = $this->db->table('jadwal_umum_unit_rapat')
                ->where('jadwal_umum_id', (int) $row['jadwal_umum_id'])
                ->get()
                ->getResultArray();
            foreach ($units as $unit) {
                $this->db->table('jadwal_unit_rapat')->ignore(true)->insert([
                    'jadwal_id'     => (int) $row['legacy_id'],
                    'unit_rapat_id' => (int) $unit['unit_rapat_id'],
                    'created_at'    => $unit['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function restoreLegacyGeneralAgendas(): void
    {
        $rooms = [];
        if ($this->db->tableExists('ruangan')) {
            $rooms = array_column(
                $this->db->table('ruangan')->select('name, id')->get()->getResultArray(),
                'name',
                'id',
            );
        }

        foreach ($this->mappedGeneralSchedules('agenda_umum') as $row) {
            $location = trim((string) ($row['lokasi_lainnya'] ?? ''));
            if ($location === '' && ! empty($row['ruangan_id'])) {
                $location = (string) ($rooms[(int) $row['ruangan_id']] ?? '');
            }

            $this->db->table('agenda_umum')->ignore(true)->insert([
                'id'                => (int) $row['legacy_id'],
                'judul'             => $row['judul'],
                'kategori'          => 'lainnya',
                'pihak_eksternal'   => $row['pihak_eksternal'],
                'tanggal'           => $row['tanggal'],
                'waktu_mulai'       => $this->timeOrDefault($row['waktu_mulai'] ?? null),
                'waktu_selesai'     => $row['waktu_selesai'],
                'lokasi'            => $location !== '' ? $location : 'Lokasi belum ditentukan',
                'sumber_informasi'  => null,
                'keterangan'        => $row['keterangan'],
                'is_publik'         => (int) $row['is_publik'],
                'created_at'        => $row['created_at'],
                'updated_at'        => $row['updated_at'],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mappedGeneralSchedules(string $sourceTable): array
    {
        return $this->db->table(self::MAP_TABLE . ' map')
            ->select('map.legacy_id, map.jadwal_umum_id, ju.*')
            ->join('jadwal_umum ju', 'ju.id = map.jadwal_umum_id')
            ->where('map.source_table', $sourceTable)
            ->orderBy('map.legacy_id', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function timeOrDefault(mixed $value, string $default = '00:00:00'): string
    {
        $value = trim((string) $value);

        return $value === '' ? $default : $value;
    }
}
