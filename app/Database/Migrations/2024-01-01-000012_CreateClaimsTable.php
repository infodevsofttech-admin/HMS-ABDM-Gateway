<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create the claims table.
 *
 * Stores insurance claim data including FHIR bundle JSON,
 * NHCX claim reference, itemized bill, and discharge summary.
 */
class CreateClaimsTable extends Migration
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
            'hospital_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'doctor_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'patient_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'claim_number'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'policy_number'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'insurer_name'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'tpa_name'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'claim_type' => [
                'type'       => 'ENUM',
                'constraint' => ['cashless', 'reimbursement'],
            ],
            'admission_date'  => ['type' => 'DATE'],
            'discharge_date'  => ['type' => 'DATE', 'null' => true],
            'diagnosis_codes' => ['type' => 'JSON', 'null' => true],
            'procedure_codes' => ['type' => 'JSON', 'null' => true],
            'itemized_bill'   => ['type' => 'JSON', 'null' => true],
            'total_amount'    => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'claim_amount'    => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'discharge_summary' => ['type' => 'TEXT', 'null' => true],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'submitted', 'approved', 'rejected', 'failed'],
                'default'    => 'pending',
            ],
            'nhcx_claim_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'fhir_bundle'   => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('claim_number');
        $this->forge->addKey('hospital_id');
        $this->forge->addKey('patient_id');
        $this->forge->addKey('nhcx_claim_id');
        $this->forge->createTable('claims');
    }

    public function down(): void
    {
        $this->forge->dropTable('claims');
    }
}
