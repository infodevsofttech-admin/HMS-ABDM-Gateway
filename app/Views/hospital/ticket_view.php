<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'Ticket #' . esc($ticket->ticket_number ?? ''); ?>

<?= $this->section('content') ?>

<?php
$ticket   = $ticket ?? null;
$messages = $messages ?? [];

$statusLabel = [
    'open'        => ['label' => 'Open',        'class' => 'hb-blue'],
    'in_progress' => ['label' => 'In Progress',  'class' => 'hb-yellow'],
    'resolved'    => ['label' => 'Resolved',     'class' => 'hb-green'],
    'closed'      => ['label' => 'Closed',       'class' => 'hb-red'],
];
$sl = $statusLabel[$ticket->status ?? 'open'] ?? ['label' => ucfirst($ticket->status ?? ''), 'class' => 'hb-blue'];
$isClosed = in_array($ticket->status ?? '', ['resolved', 'closed'], true);
?>

<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-ticket-alt"></i> Ticket <?= esc($ticket->ticket_number ?? '') ?></h5>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
            <li class="breadcrumb-item"><a href="/portal/tickets">Support</a></li>
            <li class="breadcrumb-item active"><?= esc($ticket->ticket_number ?? '') ?></li>
        </ol></nav>
    </div>
    <span class="hb <?= $sl['class'] ?>" style="font-size:13px;padding:6px 14px;"><?= $sl['label'] ?></span>
</div>

<div class="hp-content">
    <div class="row">
        <div class="col-md-8">

            <!-- Message thread -->
            <div class="hp-card">
                <div class="hp-card-head">
                    <i class="fas fa-comments"></i>
                    <?= esc($ticket->subject ?? '') ?>
                </div>
                <div class="hp-card-body" style="padding:0;">
                    <?php foreach ($messages as $msg): ?>
                    <?php $isAdmin = ($msg->sender_type === 'admin'); ?>
                    <div style="padding:18px 24px;border-bottom:1px solid #f0f3f8;display:flex;gap:14px;<?= $isAdmin ? 'background:#f0f7ff;' : '' ?>">
                        <!-- Avatar -->
                        <div style="flex-shrink:0;">
                            <div style="width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;
                                <?= $isAdmin ? 'background:var(--hp-accent);' : 'background:var(--hp-primary);' ?>">
                                <?= strtoupper(substr((string)($msg->sender_name ?? 'A'), 0, 1)) ?>
                            </div>
                        </div>
                        <!-- Body -->
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                                <span style="font-size:13px;font-weight:700;color:#1a1a2e;"><?= esc((string)($msg->sender_name ?? 'Unknown')) ?></span>
                                <?php if ($isAdmin): ?>
                                <span style="font-size:10px;background:#00897b;color:#fff;padding:1px 7px;border-radius:999px;font-weight:700;">ADMIN</span>
                                <?php else: ?>
                                <span style="font-size:10px;background:var(--hp-primary);color:#fff;padding:1px 7px;border-radius:999px;font-weight:700;">YOU</span>
                                <?php endif; ?>
                                <span style="font-size:11px;color:#adb5bd;margin-left:auto;"><?= esc((string)($msg->created_at ?? '')) ?></span>
                            </div>
                            <div style="font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;"><?= esc((string)($msg->message ?? '')) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($messages)): ?>
                    <div style="text-align:center;padding:40px;color:#6c757d;font-size:13px;">No messages yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reply form -->
            <?php if (!$isClosed): ?>
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-reply"></i> Add Reply</div>
                <div class="hp-card-body">
                    <form method="post" action="/portal/tickets/<?= (int)$ticket->id ?>/reply">
                        <?= csrf_field() ?>
                        <div class="form-group mb-3">
                            <textarea name="message" class="form-control" rows="4" required
                                      placeholder="Type your reply here..." style="font-size:13px;resize:vertical;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane mr-1"></i> Send Reply
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-secondary" style="font-size:13px;">
                <i class="fas fa-lock mr-1"></i> This ticket is <?= esc($ticket->status) ?>. No further replies are accepted.
            </div>
            <?php endif; ?>

        </div>

        <!-- Sidebar: ticket info -->
        <div class="col-md-4">
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-info-circle"></i> Ticket Details</div>
                <div class="hp-card-body" style="padding:0;">
                    <?php
                    $details = [
                        ['Ticket #',  $ticket->ticket_number ?? ''],
                        ['Status',    $sl['label']],
                        ['Category',  ucfirst($ticket->category ?? '')],
                        ['Priority',  ucfirst($ticket->priority ?? '')],
                        ['Messages',  (string)($ticket->message_count ?? 0)],
                        ['Created',   $ticket->created_at ?? ''],
                        ['Last Reply', $ticket->last_reply_at ?? '—'],
                    ];
                    foreach ($details as [$label, $val]):
                    ?>
                    <div style="display:flex;justify-content:space-between;padding:10px 16px;border-bottom:1px solid #f0f3f8;font-size:13px;">
                        <span style="color:#6c757d;font-weight:500;"><?= esc($label) ?></span>
                        <span style="color:#1a1a2e;font-weight:600;text-align:right;"><?= esc((string)$val) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="/portal/tickets" class="btn btn-outline-secondary btn-sm btn-block" style="font-size:12px;">
                <i class="fas fa-arrow-left mr-1"></i> Back to All Tickets
            </a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
