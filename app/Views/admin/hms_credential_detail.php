<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HMS Credential Detail - ABDM Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; }
        .panel { background: #fff; padding: 16px; border-radius: 10px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .row { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 10px; margin-bottom: 10px; }
        input, select, textarea, button { padding: 8px; font-family: monospace; }
        input, select, textarea { width: 100%; }
        button { background: #1d4ed8; color: #fff; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #1e40af; }
        .danger { background: #b91c1c; }
        .danger:hover { background: #7f1d1d; }
        .ok { color: #065f46; padding: 8px; background: #f0fdf4; border-radius: 4px; }
        .err { color: #b91c1c; padding: 8px; background: #fef2f2; border-radius: 4px; }
        a { color: #1d4ed8; text-decoration: none; }
        .badge { display: inline-block; padding: 4px 12px; background: #ecfeff; border-radius: 4px; }
        .badge.active { background: #d1fae5; }
        .badge.verified { background: #d1fae5; }
        .info-field { margin-bottom: 12px; }
        .info-label { font-weight: bold; color: #666; }
        .info-value { color: #333; margin-top: 4px; padding: 8px; background: #f3f4f6; border-radius: 4px; }
        .status-ok { color: #065f46; font-weight: bold; }
        .status-err { color: #b91c1c; font-weight: bold; }
        .form-section { margin-top: 20px; }
    </style>
</head>
<body>
    <p><a href="/admin/hms-access">← Back to HMS Access</a></p>
    <h1>HMS Credential Detail</h1>

    <?php if (!empty($message)): ?><div class="ok"><?= esc($message) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="err"><?= esc($error) ?></div><?php endif; ?>

    <div class="panel">
        <h2>Hospital Information</h2>
        <div class="info-field">
            <div class="info-label">Hospital Name</div>
            <div class="info-value"><?= esc((string) $credential->hospital_name) ?></div>
        </div>
        <div class="info-field">
            <div class="info-label">HFR ID</div>
            <div class="info-value"><?= esc((string) $credential->hfr_id) ?></div>
        </div>
    </div>

    <div class="panel">
        <h2>HMS Configuration</h2>
        <div class="info-field">
            <div class="info-label">HMS System Name</div>
            <div class="info-value"><?= esc((string) $credential->hms_name) ?></div>
        </div>
        <div class="info-field">
            <div class="info-label">API Endpoint</div>
            <div class="info-value"><code><?= esc((string) $credential->hms_api_endpoint) ?></code></div>
        </div>
        <div class="info-field">
            <div class="info-label">Authentication Type</div>
            <div class="info-value"><span class="badge"><?= esc((string) strtoupper($credential->hms_auth_type)) ?></span></div>
        </div>
        <div class="info-field">
            <div class="info-label">Status</div>
            <div class="info-value">
                <?php if ((int) $credential->is_active === 1): ?>
                    <span class="status-ok">● Active</span>
                <?php else: ?>
                    <span class="status-err">● Inactive</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="info-field">
            <div class="info-label">Verification Status</div>
            <div class="info-value">
                <?php if ((int) $credential->is_verified === 1): ?>
                    <span class="status-ok">✓ Verified</span><br>
                    Last Verified: <?= !empty($credential->last_verified_at) ? esc((string) $credential->last_verified_at) : 'N/A' ?>
                <?php else: ?>
                    <span class="status-err">✗ Not Verified</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="panel">
        <h2>Update Credential</h2>
        <form method="post" action="/admin/hms-credential/<?= esc((string) $credential->id) ?>/update">
            <div class="row">
                <input name="hms_api_endpoint" value="<?= esc((string) $credential->hms_api_endpoint) ?>" placeholder="HMS API Endpoint" required>
            </div>

            <?php if ($credential->hms_auth_type === 'api_key'): ?>
                <div class="row">
                    <input name="hms_api_key" type="password" placeholder="API Key (leave blank to keep current)">
                </div>
            <?php elseif ($credential->hms_auth_type === 'basic'): ?>
                <div class="row">
                    <input name="hms_password" type="password" placeholder="Password (leave blank to keep current)">
                </div>
            <?php endif; ?>

            <div class="row">
                <select name="is_active">
                    <option value="1" <?= (int) $credential->is_active === 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= (int) $credential->is_active === 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <p><button type="submit">Update Credential</button></p>
        </form>
    </div>

    <div class="panel">
        <h2>Actions</h2>
        <div class="row" style="grid-template-columns: 1fr 1fr;">
            <form method="post" action="/admin/hms-credential/<?= esc((string) $credential->id) ?>/test">
                <button type="submit" style="background: #059669;">🔄 Test Connection</button>
            </form>
            <form method="post" action="/admin/hms-credential/<?= esc((string) $credential->id) ?>/delete" onsubmit="return confirm('Are you sure you want to delete this credential?');">
                <button type="submit" class="danger">🗑 Delete Credential</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <h2>Credential Information</h2>
        <div class="info-field">
            <div class="info-label">Created</div>
            <div class="info-value"><?= esc((string) $credential->created_at) ?></div>
        </div>
        <div class="info-field">
            <div class="info-label">Last Updated</div>
            <div class="info-value"><?= esc((string) $credential->updated_at) ?></div>
        </div>
        <div class="info-field">
            <div class="info-label">Credential ID</div>
            <div class="info-value"><code><?= esc((string) $credential->id) ?></code></div>
        </div>
    </div>
</body>
</html>
