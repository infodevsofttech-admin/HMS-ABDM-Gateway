<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmHospitalUsers extends Migration
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
            'hospital_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'password_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'api_token' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
            ],
            'role' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'hospital_admin',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'last_login_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('api_token');
        $this->forge->addKey('hospital_id');
        $this->forge->addKey('is_active');
        $this->forge->addForeignKey('hospital_id', 'abdm_hospitals', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('abdm_hospital_users', true);
    }

    public function down()
    {
        $this->forge->dropTable('abdm_hospital_users', true);
    }
}
