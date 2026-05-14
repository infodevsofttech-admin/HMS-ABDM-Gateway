<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HMS Access Management - ABDM Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; }
        .panel { background: #fff; padding: 16px; border-radius: 10px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .row { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 10px; }
        input, select, textarea, button { padding: 8px; font-family: monospace; }
        input, select, textarea { width: 100%; }
        button { background: #1d4ed8; color: #fff; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #1e40af; }
        .danger { background: #b91c1c; }
        .danger:hover { background: #7f1d1d; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #ecfeff; }
        .status-ok { color: #065f46; font-weight: bold; }
        .status-err { color: #b91c1c; font-weight: bold; }
        .ok { color: #065f46; padding: 8px; background: #f0fdf4; border-radius: 4px; }
        .err { color: #b91c1c; padding: 8px; background: #fef2f2; border-radius: 4px; }
        a { color: #1d4ed8; text-decoration: none; }
        .badge { display: inline-block; padding: 2px 8px; background: #ecfeff; border-radius: 4px; font-size: 0.85em; }
        .badge.active { background: #d1fae5; }
        .badge.verified { background: #d1fae5; }
        .action-links { display: flex; gap: 8px; font-size: 0.9em; }
        .action-links form { display: inline; }
        .action-links button { padding: 4px 8px; font-size: 0.85em; }
    </style>
</head>
<body>
    <p><a href="/admin/dashboard">← Back to Dashboard</a></p>
    <h1>HMS Access Management</h1>
    
    <?php if (!empty($message)): ?><div class="ok"><?= esc($message) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="err"><?= esc($error) ?></div><?php endif; ?>

    <div class="panel">
        <h2>Add HMS Credential</h2>
        <form method="post" action="/admin/hms-credential/create">
            <div class="row">
                <select name="hospital_id" required>
                    <option value="">Select Hospital</option>
                    <?php foreach ($hospitals as $hospital): ?>
                        <option value="<?= esc((string) $hospital->id) ?>">
                            <?= esc((string) $hospital->hospital_name) ?> (<?= esc((string) $hospital->hfr_id) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input name="hms_name" placeholder="HMS System Name (e.g., Meddata HMS, Athenahealth)" required>
                <input name="hms_api_endpoint" placeholder="HMS API Endpoint (e.g., https://hms.example.com/api)" required>
                <select name="hms_auth_type" id="authType" required onchange="toggleAuthFields()">
                    <option value="api_key">API Key Authentication</option>
                    <option value="basic">Basic Authentication (Username/Password)</option>
                </select>
            </div>

            <div id="apiKeyFields" class="row" style="margin-top: 10px;">
                <input name="hms_api_key" type="password" placeholder="API Key" id="apiKeyInput">
            </div>

            <div id="basicAuthFields" class="row" style="margin-top: 10px; display: none;">
                <input name="hms_username" placeholder="Username" id="basicUsername">
                <input name="hms_password" type="password" placeholder="Password" id="basicPassword">
            </div>

            <p style="margin-top: 10px;"><button type="submit">Add HMS Credential</button></p>
        </form>
    </div>

    <script>
        function toggleAuthFields() {
            const authType = document.getElementById('authType').value;
            document.getElementById('apiKeyFields').style.display = authType === 'api_key' ? 'grid' : 'none';
            document.getElementById('basicAuthFields').style.display = authType === 'basic' ? 'grid' : 'none';
        }
    </script>

    <div class="panel">
        <h2>HMS Credentials List</h2>
        <table>
            <thead>
                <tr>
                    <th>Hospital</th>
                    <th>HMS Name</th>
                    <th>Auth Type</th>
                    <th>Status</th>
                    <th>Verified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($credentials)): ?>
                    <?php foreach ($credentials as $cred): ?>
                        <tr>
                            <td>
                                <strong><?= esc((string) $cred->hospital_name) ?></strong><br>
                                <small><?= esc((string) $cred->hfr_id) ?></small>
                            </td>
                            <td><?= esc((string) $cred->hms_name) ?></td>
                            <td><span class="badge"><?= esc((string) strtoupper($cred->hms_auth_type)) ?></span></td>
                            <td>
                                <?php if ((int) $cred->is_active === 1): ?>
                                    <span class="status-ok">● Active</span>
                                <?php else: ?>
                                    <span class="status-err">● Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $cred->is_verified === 1): ?>
                                    <span class="status-ok">✓ Verified</span><br>
                                    <small><?= !empty($cred->last_verified_at) ? esc((string) $cred->last_verified_at) : 'N/A' ?></small>
                                <?php else: ?>
                                    <span class="status-err">✗ Not Verified</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-links">
                                    <a href="/admin/hms-credential/<?= esc((string) $cred->id) ?>">View</a>
                                    <form method="post" action="/admin/hms-credential/<?= esc((string) $cred->id) ?>/test" style="display: inline;">
                                        <button type="submit" style="background: #059669;">Test Connection</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #999;">No HMS credentials found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
