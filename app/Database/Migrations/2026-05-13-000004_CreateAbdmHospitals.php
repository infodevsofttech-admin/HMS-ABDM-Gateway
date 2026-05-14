<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmHospitals extends Migration
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
            'hospital_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'hfr_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'gateway_mode' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'test',
            ],
            'contact_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'contact_email' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
                'null' => true,
            ],
            'contact_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addUniqueKey('hfr_id');
        $this->forge->addKey('gateway_mode');
        $this->forge->addKey('is_active');

        $this->forge->createTable('abdm_hospitals', true);
    }

    public function down()
    {
        $this->forge->dropTable('abdm_hospitals', true);
    }
}
