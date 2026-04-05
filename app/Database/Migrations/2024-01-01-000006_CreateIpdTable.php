<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create the ipd table (In-Patient Department admission/discharge).
 */
class CreateIpdTable extends Migration
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
            'hospital_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'doctor_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'patient_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'admission_date'   => ['type' => 'DATE'],
            'discharge_date'   => ['type' => 'DATE', 'null' => true],
            'ward'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'bed_number'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'admission_reason' => ['type' => 'TEXT'],
            'diagnosis'        => ['type' => 'TEXT', 'null' => true],
            'treatment'        => ['type' => 'JSON', 'null' => true],
            'discharge_summary'=> ['type' => 'TEXT', 'null' => true],
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
        $this->forge->createTable('ipd');
    }

    public function down(): void
    {
        $this->forge->dropTable('ipd');
    }
}
