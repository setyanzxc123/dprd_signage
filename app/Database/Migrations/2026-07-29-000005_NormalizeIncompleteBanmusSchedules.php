<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class NormalizeIncompleteBanmusSchedules extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('jadwal_banmus')) {
            return;
        }

        $items = $this->db->table('jadwal_banmus')
            ->select('id, tanggal, jam_mulai, jam_selesai, ruangan_id, lokasi_lainnya')
            ->whereIn('status', ['menunggu', 'persiapan', 'berlangsung', 'selesai'])
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        if ($items === []) {
            return;
        }

        $itemIdsWithUnits = [];
        if ($this->db->tableExists('jadwal_banmus_unit_rapat')) {
            $itemIds = array_map(
                static fn (array $item): int => (int) $item['id'],
                $items,
            );
            $unitRows = $this->db->table('jadwal_banmus_unit_rapat')
                ->select('jadwal_banmus_id')
                ->whereIn('jadwal_banmus_id', $itemIds)
                ->groupBy('jadwal_banmus_id')
                ->get()
                ->getResultArray();

            $itemIdsWithUnits = array_fill_keys(array_map(
                static fn (array $row): int => (int) $row['jadwal_banmus_id'],
                $unitRows,
            ), true);
        }

        $incompleteItemIds = [];
        foreach ($items as $item) {
            $itemId = (int) $item['id'];
            $hasLocation = (int) ($item['ruangan_id'] ?? 0) > 0
                || trim((string) ($item['lokasi_lainnya'] ?? '')) !== '';

            if (
                trim((string) ($item['tanggal'] ?? '')) === ''
                || trim((string) ($item['jam_mulai'] ?? '')) === ''
                || trim((string) ($item['jam_selesai'] ?? '')) === ''
                || ! $hasLocation
                || ! isset($itemIdsWithUnits[$itemId])
            ) {
                $incompleteItemIds[] = $itemId;
            }
        }

        if ($incompleteItemIds !== []) {
            $this->db->table('jadwal_banmus')
                ->whereIn('id', $incompleteItemIds)
                ->update(['status' => 'proyeksi']);
        }
    }

    public function down(): void
    {
        // Status lama tidak dapat dipulihkan secara aman setelah data dikoreksi.
    }
}
