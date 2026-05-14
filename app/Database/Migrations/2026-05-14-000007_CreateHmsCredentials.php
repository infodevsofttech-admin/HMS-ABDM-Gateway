<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHmsCredentials extends Migration
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
            'hms_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'hms_api_endpoint' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
            ],
            'hms_api_key' => [
                'type' => 'TEXT',
            ],
            'hms_auth_type' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'api_key',
            ],
            'hms_username' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'hms_password' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_verified' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'last_verified_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('hospital_id');
        $this->forge->addKey('is_active');
        $this->forge->addForeignKey('hospital_id', 'abdm_hospitals', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('hms_credentials', true);
    }

    public function down()
    {
        $this->forge->dropTable('hms_credentials', true);
    }
}
