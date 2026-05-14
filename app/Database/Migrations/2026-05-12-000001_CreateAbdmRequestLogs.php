<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmRequestLogs extends Migration
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
            ],
            'method' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
            ],
            'endpoint' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'status_code' => [
                'type' => 'INT',
            ],
            'response_time_ms' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'authorization_status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addUniqueKey('request_id');
        $this->forge->addKey('endpoint');
        $this->forge->addKey('status_code');
        $this->forge->addKey('created_at');

        $this->forge->createTable('abdm_request_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('abdm_request_logs', true);
    }
}
