<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bundle Logs - ABDM Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #fee2e2; }
        a { color: #1d4ed8; text-decoration: none; }
    </style>
</head>
<body>
    <p><a href="/admin/dashboard">Back to Dashboard</a></p>
    <h1>Bundle Submission Logs</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Bundle ID</th>
                <th>Consent ID</th>
                <th>HI Type</th>
                <th>Push Status</th>
                <th>Response Status</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= esc((string) $log->id) ?></td>
                    <td><?= esc((string) $log->bundle_id) ?></td>
                    <td><?= esc((string) $log->consent_id) ?></td>
                    <td><?= esc((string) $log->hi_type) ?></td>
                    <td><?= esc((string) $log->push_status) ?></td>
                    <td><?= esc((string) $log->response_status) ?></td>
                    <td><?= esc((string) $log->created_at) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
