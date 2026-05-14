<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExtendAbdmAbhaProfiles extends Migration
{
    public function up()
    {
        // Individual name parts (v3 returns firstName / middleName / lastName separately)
        $this->forge->addColumn('abdm_abha_profiles', [
            'first_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'full_name',
            ],
            'middle_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'first_name',
            ],
            'last_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'middle_name',
            ],
            // Email
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'mobile',
            ],
            'mobile_verified' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'email',
            ],
            // Primary PHR/ABHA address (e.g. 91510165305101@sbx)
            'phr_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'abha_address',
            ],
            // ABDM status as returned by API (ACTIVE / INACTIVE / DELETED)
            'abha_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'status',
            ],
            // ABHA type (STANDARD / CHILD)
            'abha_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'abha_status',
            ],
            // Address fields
            'address' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'abha_type',
            ],
            'pin_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'after'      => 'address',
            ],
            'state_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'after'      => 'pin_code',
            ],
            'state_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'state_code',
            ],
            'district_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'after'      => 'state_name',
            ],
            'district_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'district_code',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('abdm_abha_profiles', [
            'first_name', 'middle_name', 'last_name',
            'email', 'mobile_verified', 'phr_address',
            'abha_status', 'abha_type',
            'address', 'pin_code',
            'state_code', 'state_name',
            'district_code', 'district_name',
        ]);
    }
}
