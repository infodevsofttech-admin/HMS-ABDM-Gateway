<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSupportAttachments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT',  'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'ticket_id'     => ['type' => 'BIGINT',  'constraint' => 20, 'unsigned' => true],
            'message_id'    => ['type' => 'BIGINT',  'constraint' => 20, 'unsigned' => true, 'null' => true],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'stored_name'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime_type'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'file_size'     => ['type' => 'INT',     'constraint' => 11,  'unsigned' => true, 'default' => 0],
            'uploaded_by'   => ['type' => 'ENUM',    'constraint' => ['hospital','admin'], 'default' => 'hospital'],
            'created_at'    => ['type' => 'DATETIME','null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ticket_id');
        $this->forge->addKey('message_id');
        $this->forge->createTable('abdm_support_attachments');
    }

    public function down()
    {
        $this->forge->dropTable('abdm_support_attachments', true);
    }
}
