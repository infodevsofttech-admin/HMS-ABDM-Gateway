<?php

namespace App\Services;

use App\Models\SyncQueueModel;
use App\Models\AuditLogModel;

/**
 * SyncQueueService
 *
 * Manages queuing of outbound ABDM requests and retry processing for
 * offline or intermittently-connected HMS instances.
 */
class SyncQueueService
{
    protected SyncQueueModel $syncQueue;
    protected AuditLogModel  $auditLog;
    protected AbdmApiService  $abdmApi;

    public function __construct()
    {
        $this->syncQueue = new SyncQueueModel();
        $this->auditLog  = new AuditLogModel();
        $this->abdmApi   = new AbdmApiService();
    }

    /**
     * Enqueue a new sync request for later (or immediate) processing.
     *
     * @param string $hmsId      The local HMS entity identifier.
     * @param string $recordType One of: hospital, doctor, patient, opd, ipd, lab, radiology, pharmacy.
     * @param array  $payload    The FHIR or domain payload to send to ABDM.
     *
     * @return int|string The inserted queue record ID.
     */
    public function enqueue(string $hmsId, string $recordType, array $payload): int|string
    {
        return $this->syncQueue->insert([
            'hms_id'      => $hmsId,
            'record_type' => $recordType,
            'payload'     => json_encode($payload),
            'status'      => 'pending',
            'attempts'    => 0,
        ]);
    }

    /**
     * Process all pending / retry-eligible queue items.
     * Intended to be called from a CLI command or cron job.
     *
     * @return array{processed: int, succeeded: int, failed: int}
     */
    public function processPending(): array
    {
        $records   = $this->syncQueue->getPendingRecords();
        $processed = 0;
        $succeeded = 0;
        $failed    = 0;

        foreach ($records as $record) {
            $processed++;
            $attempts = (int) $record['attempts'] + 1;
            $payload  = json_decode($record['payload'], true);

            try {
                $result = $this->dispatchToAbdm($record['record_type'], $payload);

                $this->syncQueue->markSuccess($record['id'], json_encode($result));

                $this->auditLog->record(
                    'sync_success',
                    $record['record_type'],
                    $payload,
                    $result,
                    200,
                    $record['hms_id']
                );

                $succeeded++;
            } catch (\Exception $e) {
                $this->syncQueue->markFailed($record['id'], $e->getMessage(), $attempts);

                $this->auditLog->record(
                    'sync_failed',
                    $record['record_type'],
                    $payload,
                    ['error' => $e->getMessage()],
                    500,
                    $record['hms_id']
                );

                $failed++;
            }
        }

        return compact('processed', 'succeeded', 'failed');
    }

    /**
     * Route a record to the correct ABDM API method based on type.
     */
    protected function dispatchToAbdm(string $recordType, array $payload): array
    {
        return match ($recordType) {
            'hospital'  => $this->abdmApi->registerHospital($payload),
            'doctor'    => $this->abdmApi->registerDoctor($payload),
            'patient'   => $this->abdmApi->createAbha($payload),
            default     => $this->abdmApi->pushHealthRecord($payload),
        };
    }
}
