<?php

namespace App\Models;

use CodeIgniter\Model;

class AppSetting extends Model
{
    protected $table      = 'abdm_app_settings';
    protected $primaryKey = 'setting_key';
    protected $returnType = 'object';

    protected $allowedFields = ['setting_key', 'setting_value', 'updated_at'];

    /** Get a setting value */
    public static function get(string $key, string $default = ''): string
    {
        $db  = \Config\Database::connect();
        $row = $db->table('abdm_app_settings')->where('setting_key', $key)->get()->getRowObject();
        return $row ? (string)($row->setting_value ?? $default) : $default;
    }

    /** Set (upsert) a setting value */
    public static function set(string $key, string $value): void
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $exists = $db->table('abdm_app_settings')->where('setting_key', $key)->get()->getRowObject();
        if ($exists) {
            $db->table('abdm_app_settings')
               ->where('setting_key', $key)
               ->update(['setting_value' => $value, 'updated_at' => $now]);
        } else {
            $db->table('abdm_app_settings')
               ->insert(['setting_key' => $key, 'setting_value' => $value, 'updated_at' => $now]);
        }
    }

    /** Get all SMTP settings as an array */
    public static function smtp(): array
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('abdm_app_settings')
                   ->like('setting_key', 'smtp_', 'after')
                   ->get()->getResultObject();
        $out = [];
        foreach ($rows as $r) {
            $out[$r->setting_key] = $r->setting_value ?? '';
        }
        return $out;
    }
}
