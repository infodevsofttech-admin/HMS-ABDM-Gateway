<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request Logs - ABDM Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #e0f2fe; }
        a { color: #1d4ed8; text-decoration: none; }
    </style>
</head>
<body>
    <p><a href="/admin/dashboard">Back to Dashboard</a></p>
    <h1>Gateway Request Logs</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Request ID</th>
                <th>Method</th>
                <th>Endpoint</th>
                <th>Status</th>
                <th>Auth</th>
                <th>Time(ms)</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= esc((string) $log->id) ?></td>
                    <td><?= esc((string) $log->request_id) ?></td>
                    <td><?= esc((string) $log->method) ?></td>
                    <td><?= esc((string) $log->endpoint) ?></td>
                    <td><?= esc((string) $log->status_code) ?></td>
                    <td><?= esc((string) $log->authorization_status) ?></td>
                    <td><?= esc((string) $log->response_time_ms) ?></td>
                    <td><?= esc((string) $log->created_at) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
