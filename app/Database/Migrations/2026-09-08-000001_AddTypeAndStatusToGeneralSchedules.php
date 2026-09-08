<?php

namespace App\Database\Migrations;

use App\Models\JadwalUmumModel;
use CodeIgniter\Database\Migration;

class AddTypeAndStatusToGeneralSchedules extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('jadwal_umum')) {
            return;
        }

        $fields = [];
        if (! $this->db->fieldExists('jenis_agenda', 'jadwal_umum')) {
            $fields['jenis_agenda'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'rapat',
            ];
        }
        if (! $this->db->fieldExists('tanggal_mulai', 'jadwal_umum')) {
            $fields['tanggal_mulai'] = [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
            ];
        }
        if (! $this->db->fieldExists('tanggal_selesai', 'jadwal_umum')) {
            $fields['tanggal_selesai'] = [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
            ];
        }
        if (! $this->db->fieldExists('status', 'jadwal_umum')) {
            $fields['status'] = [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'menunggu',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('jadwal_umum', $fields);
        }

        $rows = $this->db->table('jadwal_umum')
            ->select('id, tanggal, waktu_mulai, waktu_selesai')
            ->get()->getResultArray();

        foreach ($rows as $row) {
            $status = JadwalUmumModel::resolveLifecycleStatus(
                (string) $row['tanggal'],
                $row['waktu_mulai'],
                $row['waktu_selesai'],
            );
            $this->db->table('jadwal_umum')
                ->where('id', (int) $row['id'])
                ->update([
                    'status'          => $status,
                    'tanggal_mulai'   => $row['tanggal'],
                    'tanggal_selesai' => $row['tanggal'],
                ]);
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('jadwal_umum')) {
            return;
        }

        foreach (['status', 'tanggal_selesai', 'tanggal_mulai', 'jenis_agenda'] as $column) {
            if ($this->db->fieldExists($column, 'jadwal_umum')) {
                $this->forge->dropColumn('jadwal_umum', $column);
            }
        }
    }
}
