<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create the opd table (Out-Patient Department encounters).
 */
class CreateOpdTable extends Migration
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
            'doctor_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'patient_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'visit_date'  => ['type' => 'DATE'],
            'chief_complaint' => ['type' => 'TEXT'],
            'diagnosis'       => ['type' => 'TEXT'],
            'prescription'    => ['type' => 'JSON', 'null' => true],
            'vitals'          => ['type' => 'JSON', 'null' => true],
            'abdm_encounter_id' => [
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
        $this->forge->addKey('doctor_id');
        $this->forge->createTable('opd');
    }

    public function down(): void
    {
        $this->forge->dropTable('opd');
    }
}
