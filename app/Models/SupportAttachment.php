<?php

namespace App\Models;

use CodeIgniter\Model;

class SupportAttachment extends Model
{
    protected $table      = 'abdm_support_attachments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'ticket_id', 'message_id', 'original_name', 'stored_name',
        'mime_type', 'file_size', 'uploaded_by', 'created_at',
    ];

    public function forMessage(int $messageId): array
    {
        return $this->where('message_id', $messageId)->findAll();
    }

    public function forTicket(int $ticketId): array
    {
        return $this->where('ticket_id', $ticketId)->orderBy('created_at', 'ASC')->findAll();
    }

    /** Resolve absolute storage path for a stored filename */
    public static function storagePath(string $storedName): string
    {
        return WRITEPATH . 'uploads/support/' . $storedName;
    }
}
