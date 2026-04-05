<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create the patients table.
 */
class CreatePatientsTable extends Migration
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
            'hospital_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'abha_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'Ayushman Bharat Health Account ID',
            ],
            'dob' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'gender' => [
                'type'       => 'ENUM',
                'constraint' => ['M', 'F', 'O'],
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'blood_group' => [
                'type'       => 'VARCHAR',
                'constraint' => 5,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('hospital_id');
        $this->forge->addKey('abha_id');
        $this->forge->createTable('patients');
    }

    public function down(): void
    {
        $this->forge->dropTable('patients');
    }
}
