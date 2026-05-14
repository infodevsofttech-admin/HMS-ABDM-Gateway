<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHospitalRegistrations extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'hospital_name'    => ['type' => 'VARCHAR', 'constraint' => 200],
            'hfr_id'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'contact_name'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'contact_email'    => ['type' => 'VARCHAR', 'constraint' => 200],
            'contact_phone'    => ['type' => 'VARCHAR', 'constraint' => 20],
            'city'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'state'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'description'      => ['type' => 'TEXT', 'null' => true],
            'desired_username'  => ['type' => 'VARCHAR', 'constraint' => 80],
            'password_hash'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending','approved','rejected'], 'default' => 'pending'],
            'admin_notes'      => ['type' => 'TEXT', 'null' => true],
            'reviewed_by'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'reviewed_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey('contact_email');
        $this->forge->createTable('abdm_hospital_registrations');
    }

    public function down(): void
    {
        $this->forge->dropTable('abdm_hospital_registrations', true);
    }
}
