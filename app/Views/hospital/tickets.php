<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'Support Tickets'; ?>

<?= $this->section('content') ?>

<?php
$tickets      = $tickets ?? [];
$filterStatus = $filterStatus ?? '';

$statusLabel = [
    'open'        => ['label' => 'Open',        'class' => 'hb-blue'],
    'in_progress' => ['label' => 'In Progress',  'class' => 'hb-yellow'],
    'resolved'    => ['label' => 'Resolved',     'class' => 'hb-green'],
    'closed'      => ['label' => 'Closed',       'class' => 'hb-red'],
];
$priorityLabel = [
    'low'    => ['label' => 'Low',    'style' => 'background:#f0fdf4;color:#166534;'],
    'medium' => ['label' => 'Medium', 'style' => 'background:#fef9c3;color:#713f12;'],
    'high'   => ['label' => 'High',   'style' => 'background:#fee2e2;color:#991b1b;'],
];
?>

<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-ticket-alt"></i> Support Tickets</h5>
        <nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="/dashboard">Home</a></li><li class="breadcrumb-item active">Support</li></ol></nav>
    </div>
    <a href="/portal/tickets/new" class="btn btn-sm btn-primary">
        <i class="fas fa-plus mr-1"></i> New Ticket
    </a>
</div>

<div class="hp-content">

    <!-- Filter bar -->
    <form method="get" action="/portal/tickets" class="hp-card" style="margin-bottom:18px;">
        <div class="hp-card-body" style="padding:14px 20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <span style="font-size:13px;font-weight:600;color:#6c757d;">Filter:</span>
            <?php foreach ([''=>'All Tickets','open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed'] as $val => $lbl): ?>
            <a href="/portal/tickets<?= $val !== '' ? '?status='.$val : '' ?>"
               class="<?= $filterStatus === $val ? 'btn btn-primary btn-sm' : 'btn btn-outline-secondary btn-sm' ?>"
               style="font-size:12px;"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="hp-card">
        <div class="hp-card-head">
            <i class="fas fa-list"></i> <?= $filterStatus !== '' ? ucfirst(str_replace('_',' ',$filterStatus)) . ' ' : '' ?>Tickets
            <span style="margin-left:auto;font-size:12px;font-weight:400;color:#6c757d;"><?= count($tickets) ?> ticket(s)</span>
        </div>
        <?php if (empty($tickets)): ?>
        <div class="hp-card-body" style="text-align:center;padding:50px 20px;color:#6c757d;">
            <i class="fas fa-inbox" style="font-size:40px;color:#dee2e6;display:block;margin-bottom:12px;"></i>
            No tickets found. <a href="/portal/tickets/new" style="color:var(--hp-primary);">Create your first ticket &rarr;</a>
        </div>
        <?php else: ?>
        <table class="hp-tbl">
            <thead><tr>
                <th>Ticket #</th>
                <th>Subject</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Messages</th>
                <th>Last Activity</th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($tickets as $t): ?>
            <?php
                $sl = $statusLabel[$t->status]   ?? ['label'=>$t->status,   'class'=>'hb-blue'];
                $pl = $priorityLabel[$t->priority] ?? ['label'=>$t->priority, 'style'=>''];
            ?>
            <tr>
                <td><code style="font-size:12px;color:var(--hp-primary);"><?= esc($t->ticket_number) ?></code></td>
                <td style="max-width:260px;">
                    <a href="/portal/tickets/<?= (int)$t->id ?>" style="color:#1a1a2e;font-weight:600;font-size:13px;"><?= esc($t->subject) ?></a>
                </td>
                <td><span style="font-size:12px;color:#6c757d;"><?= esc(ucfirst($t->category)) ?></span></td>
                <td><span class="hb" style="font-size:11px;<?= $pl['style'] ?>"><?= esc($pl['label']) ?></span></td>
                <td><span class="hb <?= $sl['class'] ?>"><?= $sl['label'] ?></span></td>
                <td style="text-align:center;"><?= (int)$t->message_count ?></td>
                <td style="font-size:12px;color:#6c757d;"><?= esc((string)($t->last_reply_at ?? $t->created_at)) ?></td>
                <td><a href="/portal/tickets/<?= (int)$t->id ?>" class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:2px 10px;">View</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<?= $this->endSection() ?>
