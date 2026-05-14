<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'ABHA Validate'; ?>

<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-search"></i> ABHA Validate <small>Quick lookup by ABHA Number</small></h3>
    </div>
</div>
<div class="clearfix"></div>

<style>
        .card { background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .stack { display: grid; gap: 16px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, select, button { width: 100%; font: inherit; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
        button { background: #2563eb; color: #fff; border: 0; cursor: pointer; font-weight: 600; }
        button:hover { background: #1d4ed8; }
        .note { color: #475569; font-size: 13px; }
        .ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 10px; border-radius: 8px; }
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px; border-radius: 8px; }
        .mono { white-space: pre-wrap; background: #0b1020; color: #cbd5e1; border-radius: 8px; padding: 12px; font-family: Consolas, Monaco, monospace; font-size: 13px; }
        .pill { display: inline-block; padding: 2px 10px; border-radius: 999px; background: #e2e8f0; font-size: 12px; font-weight: 700; }
        .pill.good { background: #dcfce7; color: #065f46; }
        .pill.bad { background: #fee2e2; color: #991b1b; }
        .row { display: flex; gap: 16px; }
        .row > div { flex: 1; }
    .stack { max-width: 820px; }
</style>

<div class="stack">
    <div class="stack">
        <?php if (!empty($message)): ?>
            <div class="ok"><?= esc((string) $message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="err"><?= esc((string) $error) ?></div>
        <?php endif; ?>
        <div class="card">
            <p class="note">This guided flow validates an ABHA number through the gateway, stores the verified profile in the gateway master table, and keeps the request log for sandbox-to-live approval evidence.</p>
            <form method="post" action="/admin/m1/abha-validate">
                <?= csrf_field() ?>
                <label for="abha_id">ABHA Number</label>
                <input type="text" id="abha_id" name="abha_id" maxlength="17" placeholder="11-1111-1111-1111" required />

                <label for="mode">Mode</label>
                <select id="mode" name="mode" required>
                    <option value="sandbox">Sandbox</option>
                    <option value="live">Live</option>
                </select>
                <button type="submit">Validate</button>
            </form>
        </div>

        <?php if (!empty($lastRun) && is_array($lastRun)): ?>
        <div class="card">
            <h2>Last Validation</h2>
            <p><strong>Status:</strong> HTTP <?= esc((string) ($lastRun['statusCode'] ?? '')) ?></p>
            <p><strong>Request ID:</strong> <?= esc((string) ($lastRun['requestId'] ?? '')) ?></p>
        </div>
        <?php endif; ?>

        <?php if (!empty($abhaUser)): ?>
        <div class="card">
            <h2>ABHA User Details</h2>
            <div class="mono"><?= esc(json_encode($abhaUser, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($profiles) && is_array($profiles)): ?>
        <div class="card">
            <h2>Recent Verified Profiles</h2>
            <?php foreach ($profiles as $profile): ?>
                <div class="row">
                    <div><strong>ABHA:</strong> <?= esc((string) ($profile->abha_number ?? '')) ?></div>
                    <div><strong>Name:</strong> <?= esc((string) ($profile->full_name ?? '')) ?></div>
                    <div><strong>Verified:</strong> <?= esc((string) ($profile->last_verified_at ?? '')) ?></div>
                </div>
            <?php endforeach; ?>
            <p><a href="/admin/m1/abha-profiles">View all saved ABHA profiles</a></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
