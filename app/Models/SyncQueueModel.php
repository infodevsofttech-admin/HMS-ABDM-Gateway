<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * SyncQueue Model
 *
 * Stores outbound requests that need to be forwarded to ABDM APIs,
 * supporting offline queuing and retry logic.
 */
class SyncQueueModel extends Model
{
    protected $table      = 'sync_queue';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'hms_id',
        'record_type',
        'payload',
        'status',
        'attempts',
        'last_attempted_at',
        'next_retry_at',
        'abdm_response',
        'error_message',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation rules
    protected $validationRules = [
        'hms_id'      => 'required|max_length[100]',
        'record_type' => 'required|in_list[hospital,doctor,patient,opd,ipd,lab,radiology,pharmacy]',
        'payload'     => 'required',
        'status'      => 'required|in_list[pending,processing,success,failed]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Fetch all records that are pending or eligible for retry.
     */
    public function getPendingRecords(int $limit = 50): array
    {
        return $this->where('status', 'pending')
                    ->orWhere(static function ($query): void {
                        $query->where('status', 'failed')
                              ->where('attempts <', 3)
                              ->where('next_retry_at <=', date('Y-m-d H:i:s'));
                    })
                    ->orderBy('created_at', 'ASC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Mark a queue record as successfully processed.
     */
    public function markSuccess(int $id, string $abdmResponse): bool
    {
        return $this->update($id, [
            'status'           => 'success',
            'abdm_response'    => $abdmResponse,
            'last_attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark a queue record as failed and schedule a retry with exponential back-off.
     */
    public function markFailed(int $id, string $errorMessage, int $attempts): bool
    {
        $delaySecs   = min(3600, 60 * (2 ** ($attempts - 1)));
        $nextRetryAt = date('Y-m-d H:i:s', time() + $delaySecs);

        return $this->update($id, [
            'status'            => $attempts >= 3 ? 'failed' : 'pending',
            'error_message'     => $errorMessage,
            'attempts'          => $attempts,
            'last_attempted_at' => date('Y-m-d H:i:s'),
            'next_retry_at'     => $nextRetryAt,
        ]);
    }
}
