<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Trail - ABDM Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #ede9fe; }
        a { color: #1d4ed8; text-decoration: none; }
    </style>
</head>
<body>
    <p><a href="/admin/dashboard">Back to Dashboard</a></p>
    <h1>ABDM Audit Trail</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Request ID</th>
                <th>Action</th>
                <th>Patient ABHA</th>
                <th>Consent ID</th>
                <th>Status</th>
                <th>Performed By</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= esc((string) $log->id) ?></td>
                    <td><?= esc((string) $log->request_id) ?></td>
                    <td><?= esc((string) $log->action) ?></td>
                    <td><?= esc((string) $log->patient_abha) ?></td>
                    <td><?= esc((string) $log->consent_id) ?></td>
                    <td><?= esc((string) $log->action_status) ?></td>
                    <td><?= esc((string) $log->performed_by) ?></td>
                    <td><?= esc((string) $log->created_at) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
