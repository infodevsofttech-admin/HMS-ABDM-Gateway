<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create the radiology table (imaging study records).
 */
class CreateRadiologyTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'hospital_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'patient_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'doctor_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'modality'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'body_part'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'study_date'  => ['type' => 'DATE'],
            'findings'    => ['type' => 'TEXT', 'null' => true],
            'report_url'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'abdm_document_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'sync_status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'synced', 'failed'],
                'default'    => 'pending',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('hospital_id');
        $this->forge->addKey('patient_id');
        $this->forge->createTable('radiology');
    }

    public function down(): void
    {
        $this->forge->dropTable('radiology');
    }
}
