<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMeetingTranscriptionAndMinutesTables extends Migration
{
    public function up(): void
    {
        // 1. Tabel meeting_transcription_jobs
        if (! $this->db->tableExists('meeting_transcription_jobs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'jadwal_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'umum',
                ],
                'jadwal_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'audio_filename' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'audio_path' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'audio_size' => [
                    'type'       => 'BIGINT',
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'audio_duration' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'queued',
                ],
                'cancel_requested' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'total_chunks' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'completed_chunks' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'progress_percent' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'current_step' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                ],
                'error_message' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                    'default' => null,
                ],
                'created_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
            ]);

            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('status');
            $this->forge->addKey(['jadwal_type', 'jadwal_id']);
            $this->forge->addKey('created_by');
            $this->forge->createTable('meeting_transcription_jobs', true);
        }

        // 2. Tabel meeting_minutes
        if (! $this->db->tableExists('meeting_minutes')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'job_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'jadwal_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'umum',
                ],
                'jadwal_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'judul_rapat' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'tanggal_rapat' => [
                    'type'    => 'DATE',
                    'null'    => true,
                    'default' => null,
                ],
                'transcripts_dir' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                ],
                'ringkasan_eksekutif' => [
                    'type'    => 'LONGTEXT',
                    'null'    => true,
                    'default' => null,
                ],
                'status_verifikasi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'draft',
                ],
                'verified_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'verified_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
                'updated_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                ],
            ]);

            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('job_id');
            $this->forge->addKey(['jadwal_type', 'jadwal_id']);
            $this->forge->addKey('status_verifikasi');
            $this->forge->createTable('meeting_minutes', true);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('meeting_minutes', true);
        $this->forge->dropTable('meeting_transcription_jobs', true);
    }
}
