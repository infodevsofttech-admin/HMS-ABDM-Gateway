<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmTestSubmissionLogs extends Migration
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
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'endpoint' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'http_status' => [
                'type' => 'INT',
                'default' => 200,
            ],
            'request_payload' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'response_payload' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addKey('request_id');
        $this->forge->addKey('hospital_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('event_type');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('hospital_id', 'abdm_hospitals', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'abdm_hospital_users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('abdm_test_submission_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('abdm_test_submission_logs', true);
    }
}
