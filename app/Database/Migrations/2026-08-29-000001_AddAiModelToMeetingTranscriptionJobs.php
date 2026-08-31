<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAiModelToMeetingTranscriptionJobs extends Migration
{
    public function up(): void
    {
        $fields = [
            'ai_model' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'error_message',
            ],
        ];

        $this->forge->addColumn('meeting_transcription_jobs', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('meeting_transcription_jobs', 'ai_model');
    }
}
