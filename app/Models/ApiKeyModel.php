<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ApiKeyModel
 *
 * Manages API keys for local HMS facilities connecting to this gateway.
 * Each registered facility gets one 256-bit (64-char hex) key.
 * Keys are sent in the X-API-Key HTTP header on every /sync/* call.
 */
class ApiKeyModel extends Model
{
    protected $table      = 'api_keys';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'hospital_name',
        'hms_id',
        'api_key',
        'contact_email',
        'contact_phone',
        'state',
        'district',
        'is_active',
        'last_used_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'hospital_name' => 'required|max_length[255]',
        'hms_id'        => 'required|max_length[100]|is_unique[api_keys.hms_id,id,{id}]',
        'api_key'       => 'required|max_length[64]',
    ];

    // -------------------------------------------------------------------------
    // Factory helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a cryptographically secure 64-character hex API key.
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Register a new HMS facility and return its generated API key.
     *
     * @param array<string, mixed> $data  hospital_name, hms_id, contact_email, contact_phone, state, district
     * @return string  The plaintext API key (shown once to the admin).
     */
    public function registerFacility(array $data): string
    {
        $key = self::generateKey();

        $this->insert([
            'hospital_name' => $data['hospital_name'],
            'hms_id'        => $data['hms_id'],
            'api_key'       => $key,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'state'         => $data['state']         ?? null,
            'district'      => $data['district']      ?? null,
            'is_active'     => 1,
        ]);

        return $key;
    }

    /**
     * Regenerate the API key for an existing facility.
     * Returns the new plaintext key.
     */
    public function regenerateKey(int $id): string
    {
        $key = self::generateKey();
        $this->update($id, ['api_key' => $key]);

        return $key;
    }

    // -------------------------------------------------------------------------
    // Lookup helpers
    // -------------------------------------------------------------------------

    /**
     * Find an active facility by API key.
     * Also updates last_used_at timestamp.
     *
     * @return array<string, mixed>|null
     */
    public function findByKey(string $apiKey): ?array
    {
        $row = $this->where('api_key', $apiKey)
                    ->where('is_active', 1)
                    ->first();

        if ($row !== null) {
            $this->update($row['id'], ['last_used_at' => date('Y-m-d H:i:s')]);
        }

        return $row ?: null;
    }

    /**
     * Toggle active status (enable / disable) for a facility.
     */
    public function toggleActive(int $id): void
    {
        $row = $this->find($id);
        if ($row !== null) {
            $this->update($id, ['is_active' => $row['is_active'] ? 0 : 1]);
        }
    }
}
