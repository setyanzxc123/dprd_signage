<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

class MigrateLegacySchedulesToGeneralSchedules extends Migration
{
    private const MAP_TABLE = 'jadwal_umum_legacy_map';

    public function up(): void
    {
        if (! $this->db->tableExists('jadwal_umum')
            || ! $this->db->tableExists('jadwal_umum_unit_rapat')) {
            throw new RuntimeException('Tabel Jadwal Umum belum tersedia.');
        }

        $this->createMapTable();
        $this->migrateLegacySchedules();
        $this->migrateLegacyGeneralAgendas();
    }

    public function down(): void
    {
        if (! $this->db->tableExists(self::MAP_TABLE)) {
            return;
        }

        $ids = array_map(
            'intval',
            array_column(
                $this->db->table(self::MAP_TABLE)
                    ->select('jadwal_umum_id')
                    ->get()
                    ->getResultArray(),
                'jadwal_umum_id'
            )
        );
        if ($ids !== []) {
            if ($this->db->tableExists('jadwal_umum_unit_rapat')) {
                $this->db->table('jadwal_umum_unit_rapat')
                    ->whereIn('jadwal_umum_id', $ids)
                    ->delete();
            }
            $this->db->table('jadwal_umum')->whereIn('id', $ids)->delete();
        }

        $this->forge->dropTable(self::MAP_TABLE, true);
    }

    private function createMapTable(): void
    {
        if ($this->db->tableExists(self::MAP_TABLE)) {
            return;
        }

        $this->forge->addField([
            'source_table'     => ['type' => 'VARCHAR', 'constraint' => 30],
            'legacy_id'        => ['type' => 'INT'],
            'jadwal_umum_id'   => ['type' => 'INT'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey(['source_table', 'legacy_id'], true);
        $this->forge->addKey('jadwal_umum_id', true);
        $this->forge->addForeignKey('jadwal_umum_id', 'jadwal_umum', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable(self::MAP_TABLE, true);
    }

    private function migrateLegacySchedules(): void
    {
        if (! $this->db->tableExists('jadwal')) {
            return;
        }

        foreach ($this->db->table('jadwal')->orderBy('id', 'ASC')->get()->getResultArray() as $legacy) {
            $legacyId = (int) $legacy['id'];
            if ($this->mappedId('jadwal', $legacyId) !== null) {
                continue;
            }

            $row = [
                'judul'           => (string) $legacy['judul'],
                'tanggal'         => (string) $legacy['tanggal'],
                'waktu_mulai'     => $this->nullable($legacy['waktu_mulai'] ?? null),
                'waktu_selesai'   => $this->nullable($legacy['waktu_selesai'] ?? null),
                'ruangan_id'      => empty($legacy['ruangan_id']) ? null : (int) $legacy['ruangan_id'],
                'lokasi_lainnya'  => $this->nullable($legacy['lokasi_lainnya'] ?? null),
                'pihak_eksternal' => null,
                'is_publik'       => (int) ($legacy['is_publik'] ?? 0),
                'keterangan'      => $this->nullable($legacy['keterangan'] ?? null),
                'created_at'      => $legacy['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at'      => $legacy['updated_at'] ?? date('Y-m-d H:i:s'),
            ];
            $this->db->table('jadwal_umum')->insert($row);
            $newId = (int) $this->db->insertID();
            $this->recordMapping('jadwal', $legacyId, $newId);
            $this->copyLegacyUnits($legacyId, $newId);
        }
    }

    private function migrateLegacyGeneralAgendas(): void
    {
        if (! $this->db->tableExists('agenda_umum')) {
            return;
        }

        $rooms = [];
        if ($this->db->tableExists('ruangan')) {
            foreach ($this->db->table('ruangan')->select('id, name')->get()->getResultArray() as $room) {
                $rooms[$this->normalize((string) $room['name'])] = (int) $room['id'];
            }
        }

        foreach ($this->db->table('agenda_umum')->orderBy('id', 'ASC')->get()->getResultArray() as $legacy) {
            $legacyId = (int) $legacy['id'];
            if ($this->mappedId('agenda_umum', $legacyId) !== null) {
                continue;
            }

            $location = trim((string) ($legacy['lokasi'] ?? ''));
            $roomId = $rooms[$this->normalize($location)] ?? null;
            $description = $this->nullable($legacy['keterangan'] ?? null);
            $sourceInformation = trim((string) ($legacy['sumber_informasi'] ?? ''));
            if ($sourceInformation !== '') {
                $description = trim(($description !== null ? $description . "\n\n" : '')
                    . 'Sumber informasi: ' . $sourceInformation);
            }

            $this->db->table('jadwal_umum')->insert([
                'judul'           => (string) $legacy['judul'],
                'tanggal'         => (string) $legacy['tanggal'],
                'waktu_mulai'     => $this->nullable($legacy['waktu_mulai'] ?? null),
                'waktu_selesai'   => $this->nullable($legacy['waktu_selesai'] ?? null),
                'ruangan_id'      => $roomId,
                'lokasi_lainnya'  => $roomId === null
                    ? ($location !== '' ? $location : 'Lokasi belum ditentukan')
                    : null,
                'pihak_eksternal' => $this->nullable($legacy['pihak_eksternal'] ?? null),
                'is_publik'       => (int) ($legacy['is_publik'] ?? 0),
                'keterangan'      => $description,
                'created_at'      => $legacy['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at'      => $legacy['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
            $this->recordMapping('agenda_umum', $legacyId, (int) $this->db->insertID());
        }
    }

    private function copyLegacyUnits(int $legacyId, int $newId): void
    {
        if (! $this->db->tableExists('jadwal_unit_rapat')) {
            return;
        }

        $fields = 'unit_rapat_id'
            . ($this->db->fieldExists('created_at', 'jadwal_unit_rapat') ? ', created_at' : '');
        $rows = $this->db->table('jadwal_unit_rapat')
            ->select($fields)
            ->where('jadwal_id', $legacyId)
            ->get()
            ->getResultArray();
        foreach ($rows as $row) {
            $unitId = (int) $row['unit_rapat_id'];
            $exists = $this->db->table('jadwal_umum_unit_rapat')
                ->where('jadwal_umum_id', $newId)
                ->where('unit_rapat_id', $unitId)
                ->countAllResults() > 0;
            if (! $exists) {
                $this->db->table('jadwal_umum_unit_rapat')->insert([
                    'jadwal_umum_id' => $newId,
                    'unit_rapat_id'  => $unitId,
                    'created_at'     => $row['created_at'] ?? date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    private function mappedId(string $sourceTable, int $legacyId): ?int
    {
        $row = $this->db->table(self::MAP_TABLE)
            ->select('jadwal_umum_id')
            ->where('source_table', $sourceTable)
            ->where('legacy_id', $legacyId)
            ->get()
            ->getRowArray();

        return $row === null ? null : (int) $row['jadwal_umum_id'];
    }

    private function recordMapping(string $sourceTable, int $legacyId, int $newId): void
    {
        $this->db->table(self::MAP_TABLE)->insert([
            'source_table'   => $sourceTable,
            'legacy_id'      => $legacyId,
            'jadwal_umum_id' => $newId,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalize(string $value): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($value))) ?? '';
    }
}
