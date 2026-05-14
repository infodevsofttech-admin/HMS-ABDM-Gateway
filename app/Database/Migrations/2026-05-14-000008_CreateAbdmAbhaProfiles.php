<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateAbdmAbhaProfiles extends Migration
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
                'null' => true,
            ],
            'user_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'abha_number' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'abha_address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'full_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'gender' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'mobile' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'date_of_birth' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'year_of_birth' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'verified',
            ],
            'last_request_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'last_verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'profile_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('hospital_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('abha_number', false, true);
        $this->forge->addKey('last_verified_at');
        $this->forge->addForeignKey('hospital_id', 'abdm_hospitals', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'abdm_hospital_users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('abdm_abha_profiles', true);
    }

    public function down()
    {
        $this->forge->dropTable('abdm_abha_profiles', true);
    }
}
