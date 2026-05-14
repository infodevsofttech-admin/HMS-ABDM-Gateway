<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'Ticket #' . esc($ticket->ticket_number ?? ''); ?>

<?= $this->section('content') ?>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<?php
$ticket      = $ticket      ?? null;
$messages    = $messages    ?? [];
$attachments = $attachments ?? [];

$statusLabel = [
    'open'        => ['label' => 'Open',        'class' => 'hb-blue'],
    'in_progress' => ['label' => 'In Progress',  'class' => 'hb-yellow'],
    'resolved'    => ['label' => 'Resolved',     'class' => 'hb-green'],
    'closed'      => ['label' => 'Closed',       'class' => 'hb-red'],
];
$sl       = $statusLabel[$ticket->status ?? 'open'] ?? ['label' => ucfirst($ticket->status ?? ''), 'class' => 'hb-blue'];
$isClosed = in_array($ticket->status ?? '', ['resolved','closed'], true);

function attachIcon(string $mime): string {
    if (str_contains($mime,'pdf'))   return 'fa-file-pdf';
    if (str_contains($mime,'image')) return 'fa-file-image';
    if (str_contains($mime,'word') || str_contains($mime,'doc')) return 'fa-file-word';
    if (str_contains($mime,'sheet') || str_contains($mime,'excel') || str_contains($mime,'xls')) return 'fa-file-excel';
    if (str_contains($mime,'zip')  || str_contains($mime,'rar'))  return 'fa-file-archive';
    return 'fa-file-alt';
}
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
                    <i class="fas fa-comments"></i> <?= esc($ticket->subject ?? '') ?>
                </div>
                <div class="hp-card-body" style="padding:0;">
                    <?php foreach ($messages as $msg): ?>
                    <?php $isAdmin = ($msg->sender_type === 'admin'); ?>
                    <div style="padding:18px 24px;border-bottom:1px solid #f0f3f8;display:flex;gap:14px;<?= $isAdmin ? 'background:#f0f7ff;' : '' ?>">
                        <div style="flex-shrink:0;">
                            <div style="width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:700;color:#fff;<?= $isAdmin ? 'background:var(--hp-accent);' : 'background:var(--hp-primary);' ?>">
                                <?= strtoupper(substr((string)($msg->sender_name ?? 'A'), 0, 1)) ?>
                            </div>
                        </div>
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
                            <!-- Rich text rendered -->
                            <div class="ql-editor" style="padding:0;font-size:13px;color:#374151;"><?= ($msg->message ?? '') ?></div>
                            <!-- Attachments for this message -->
                            <?php if (!empty($attachments[(int)$msg->id])): ?>
                            <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:8px;">
                                <?php foreach ($attachments[(int)$msg->id] as $att): ?>
                                <a href="/portal/tickets/attachment/<?= (int)$att->id ?>" target="_blank"
                                   style="display:flex;align-items:center;gap:7px;padding:5px 12px;background:#f8f9fb;border:1px solid var(--hp-border);border-radius:6px;font-size:12px;color:var(--hp-primary);text-decoration:none;">
                                    <i class="fas <?= attachIcon((string)($att->mime_type ?? '')) ?>"></i>
                                    <?= esc($att->original_name) ?>
                                    <span style="color:#adb5bd;">(<?= round((int)$att->file_size/1024, 1) ?> KB)</span>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
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
                    <form method="post" action="/portal/tickets/<?= (int)$ticket->id ?>/reply" enctype="multipart/form-data" id="replyForm">
                        <?= csrf_field() ?>
                        <div class="form-group mb-3">
                            <div id="replyQuill" style="min-height:130px;font-size:13px;background:#fff;"></div>
                            <input type="hidden" name="message" id="replyMessage">
                        </div>
                        <div class="form-group mb-3">
                            <label style="font-size:12px;font-weight:600;color:#6c757d;"><i class="fas fa-paperclip mr-1"></i> Attach files (optional, max 5MB each)</label>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <label for="replyFiles" class="btn btn-outline-secondary btn-sm" style="font-size:12px;margin:0;cursor:pointer;">
                                    <i class="fas fa-cloud-upload-alt mr-1"></i> Browse Files
                                </label>
                                <input type="file" id="replyFiles" name="attachments[]" multiple
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt,.zip"
                                       style="display:none;" onchange="showReplyFiles(this.files)">
                                <span id="replyFileCount" style="font-size:12px;color:#6c757d;"></span>
                            </div>
                            <div id="replyFileList" style="margin-top:6px;"></div>
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

        <!-- Sidebar -->
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

<?= $this->section('scripts') ?>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
<?php if (!$isClosed): ?>
var replyQuill = new Quill('#replyQuill', {
    theme: 'snow',
    placeholder: 'Type your reply...',
    modules: {
        toolbar: [
            ['bold','italic','underline'],
            [{'list':'ordered'},{'list':'bullet'}],
            ['blockquote','clean']
        ]
    }
});
document.getElementById('replyForm').addEventListener('submit', function() {
    document.getElementById('replyMessage').value = replyQuill.root.innerHTML;
});
function showReplyFiles(files) {
    var list = document.getElementById('replyFileList');
    var cnt  = document.getElementById('replyFileCount');
    cnt.textContent = files.length + ' file(s) selected';
    list.innerHTML  = '';
    for (var i = 0; i < files.length; i++) {
        list.innerHTML += '<div style="font-size:12px;padding:3px 0;color:#1a1a2e;">' +
            '<i class="fas fa-file" style="color:var(--hp-primary);margin-right:5px;"></i>' +
            files[i].name.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</div>';
    }
}
<?php endif; ?>
</script>
<?= $this->endSection() ?>
