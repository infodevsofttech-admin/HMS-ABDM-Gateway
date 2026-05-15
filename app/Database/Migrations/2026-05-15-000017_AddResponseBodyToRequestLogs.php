<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddResponseBodyToRequestLogs extends Migration
{
    public function up()
    {
        $this->db->query(
            'ALTER TABLE abdm_request_logs ADD COLUMN response_body TEXT DEFAULT NULL AFTER error_message'
        );
    }

    public function down()
    {
        $this->db->query(
            'ALTER TABLE abdm_request_logs DROP COLUMN response_body'
        );
    }
}
