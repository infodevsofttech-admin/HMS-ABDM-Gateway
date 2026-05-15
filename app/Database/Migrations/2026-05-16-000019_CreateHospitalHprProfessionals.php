<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHospitalHprProfessionals extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'hospital_id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 200],
            'hpr_id'              => ['type' => 'VARCHAR', 'constraint' => 200],
            'registration_number' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'specialization'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'department'          => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'designation'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_active'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('hospital_id');
        $this->forge->addKey('hpr_id');
        $this->forge->addUniqueKey(['hospital_id', 'hpr_id']);

        $this->forge->createTable('hospital_hpr_professionals', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('hospital_hpr_professionals', true);
    }
}
