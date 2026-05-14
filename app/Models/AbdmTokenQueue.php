<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class AbdmTokenQueue extends Model
{
    protected $table         = 'abdm_token_queue';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $returnType    = 'object';

    protected $allowedFields = [
        'hospital_id', 'abha_number', 'abha_address', 'patient_name',
        'gender', 'day_of_birth', 'month_of_birth', 'year_of_birth',
        'phone', 'hip_id', 'context', 'hpr_id',
        'token_number', 'token_date', 'status',
        'request_id', 'on_share_sent', 'share_request_json',
    ];

    /** Next sequential token number for today. */
    public function nextTokenNumber(): int
    {
        $today = date('Y-m-d');
        $max   = $this->where('token_date', $today)->selectMax('token_number')->first();
        return (int) ($max->token_number ?? 0) + 1;
    }
}
