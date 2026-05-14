<?php

namespace App\Models;

use CodeIgniter\Model;

class SupportTicket extends Model
{
    protected $table      = 'abdm_support_tickets';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'ticket_number', 'hospital_id', 'subject', 'category', 'priority',
        'status', 'created_by_user_id', 'message_count', 'last_reply_at', 'last_reply_by',
    ];

    /** Generate next ticket number: TKT-YYYYMMDD-NNNN */
    public function nextTicketNumber(): string
    {
        $prefix = 'TKT-' . date('Ymd') . '-';
        $row = $this->db->query(
            "SELECT MAX(CAST(SUBSTRING_INDEX(ticket_number, '-', -1) AS UNSIGNED)) AS mx
             FROM abdm_support_tickets WHERE ticket_number LIKE ?",
            [$prefix . '%']
        )->getRow();
        $next = (int)($row->mx ?? 0) + 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /** Count open/in-progress tickets with no activity for 7+ days */
    public function countStale(): int
    {
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM abdm_support_tickets
             WHERE status NOT IN ('closed','resolved')
               AND (last_reply_at IS NULL OR last_reply_at < DATE_SUB(NOW(), INTERVAL 7 DAY))"
        )->getRow();
        return (int)($row->cnt ?? 0);
    }

    /** Tickets with hospital name joined */
    public function withHospital()
    {
        return $this->db->table('abdm_support_tickets t')
            ->select('t.*, h.hospital_name')
            ->join('abdm_hospitals h', 'h.id = t.hospital_id', 'left');
    }
}
