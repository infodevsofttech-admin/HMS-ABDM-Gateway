<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmAuditTrail extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'request_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'patient_abha' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'consent_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'hi_types' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'action_status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'details' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'performed_by' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addKey('patient_abha');
        $this->forge->addKey('consent_id');
        $this->forge->addKey('created_at');

        $this->forge->createTable('abdm_audit_trail', true);
    }

    public function down()
    {
        $this->forge->dropTable('abdm_audit_trail', true);
    }
}
