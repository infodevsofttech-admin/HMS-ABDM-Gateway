<?php

namespace App\Models;

use CodeIgniter\Model;

class SupportMessage extends Model
{
    protected $table      = 'abdm_support_messages';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'ticket_id', 'message', 'sender_type', 'sender_id', 'sender_name', 'created_at',
    ];

    public function forTicket(int $ticketId): array
    {
        return $this->where('ticket_id', $ticketId)->orderBy('created_at', 'ASC')->findAll();
    }
}
