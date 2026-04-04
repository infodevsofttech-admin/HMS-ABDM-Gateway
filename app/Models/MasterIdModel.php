<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * MasterIdModel
 *
 * Stores the mapping between local HMS entity IDs and the external ABDM
 * master identifiers (HFR ID, HPR ID, ABHA ID, etc.).
 */
class MasterIdModel extends Model
{
    protected $table      = 'master_ids';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'hms_id',
        'entity_type',
        'abdm_id',
        'abdm_id_type',
        'metadata',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'hms_id'       => 'required|max_length[100]',
        'entity_type'  => 'required|in_list[hospital,doctor,patient]',
        'abdm_id'      => 'required|max_length[100]',
        'abdm_id_type' => 'required|in_list[HFR_ID,HPR_ID,ABHA_ID]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Find the ABDM master ID for a given HMS entity.
     */
    public function findByHmsId(string $hmsId, string $entityType): ?array
    {
        return $this->where('hms_id', $hmsId)
                    ->where('entity_type', $entityType)
                    ->first();
    }

    /**
     * Upsert an ABDM ID mapping.
     */
    public function upsert(string $hmsId, string $entityType, string $abdmId, string $abdmIdType, array $metadata = []): bool
    {
        $existing = $this->findByHmsId($hmsId, $entityType);

        $data = [
            'hms_id'       => $hmsId,
            'entity_type'  => $entityType,
            'abdm_id'      => $abdmId,
            'abdm_id_type' => $abdmIdType,
            'metadata'     => json_encode($metadata),
        ];

        if ($existing !== null) {
            return $this->update($existing['id'], $data);
        }

        return $this->insert($data) !== false;
    }
}
