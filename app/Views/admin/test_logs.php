<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Submission Logs - ABDM Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #d1fae5; }
        a { color: #1d4ed8; text-decoration: none; }
        .small { font-size: 12px; max-width: 360px; overflow: auto; }
    </style>
</head>
<body>
    <p><a href="/admin/dashboard">Back to Dashboard</a></p>
    <h1>ABDM Test Submission Logs</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Time</th>
                <th>Hospital</th>
                <th>User</th>
                <th>Event</th>
                <th>Endpoint</th>
                <th>Status</th>
                <th>Request</th>
                <th>Response</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= esc((string) $log->id) ?></td>
                    <td><?= esc((string) $log->created_at) ?></td>
                    <td><?= esc((string) ($log->hospital_name ?? 'N/A')) ?></td>
                    <td><?= esc((string) ($log->username ?? 'N/A')) ?></td>
                    <td><?= esc((string) ($log->event_type ?? '')) ?></td>
                    <td><?= esc((string) $log->endpoint) ?></td>
                    <td><?= esc((string) $log->http_status) ?></td>
                    <td class="small"><pre><?= esc((string) $log->request_payload) ?></pre></td>
                    <td class="small"><pre><?= esc((string) $log->response_payload) ?></pre></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
