<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * SyncQueueModel
 *
 * Offline sync queue for HMS → ABDM operations.
 * Entries are created before each ABDM API call and updated with the result.
 * A background job can process pending/failed entries for retry.
 */
class SyncQueueModel extends Model
{
    protected $table            = 'sync_queue';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'entity_type',
        'entity_id',
        'action',
        'payload',
        'status',
        'attempts',
        'last_error',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Add an entry to the sync queue.
     *
     * @param array<string, mixed> $payload
     */
    public function enqueue(string $entityType, int|string $entityId, string $action, array $payload): int|string
    {
        return $this->insert([
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'action'      => $action,
            'payload'     => json_encode($payload),
            'status'      => 'pending',
            'attempts'    => 0,
        ]);
    }

    /**
     * Mark a queue entry as successfully completed.
     *
     * @param array<string, mixed>|null $responseData
     */
    public function markCompleted(int|string $queueId, ?array $responseData = null): bool
    {
        return $this->update($queueId, [
            'status'  => 'completed',
            'payload' => json_encode($responseData),
        ]);
    }

    /**
     * Mark a queue entry as failed and increment attempt count.
     */
    public function markFailed(int|string $queueId, string $error): bool
    {
        $entry = $this->find($queueId);
        if ($entry === null) {
            return false;
        }

        return $this->update($queueId, [
            'status'     => 'failed',
            'attempts'   => ((int) $entry['attempts']) + 1,
            'last_error' => $error,
        ]);
    }

    /**
     * Return all pending or failed entries eligible for retry.
     *
     * @param int $maxAttempts Stop retrying after this many failures
     * @return array<int, array<string, mixed>>
     */
    public function getPendingForRetry(int $maxAttempts = 3): array
    {
        return $this->whereIn('status', ['pending', 'failed'])
                    ->where('attempts <', $maxAttempts)
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }
}
