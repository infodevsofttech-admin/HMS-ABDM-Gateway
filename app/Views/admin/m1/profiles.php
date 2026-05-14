<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Saved ABHA Profiles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; color: #111827; }
        .card { background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { background: #f8fafc; }
        .mono { white-space: pre-wrap; font-family: Consolas, Monaco, monospace; font-size: 12px; max-width: 520px; }
        a { color: #1d4ed8; text-decoration: none; }
    </style>
</head>
<body>
    <?php $profiles = $profiles ?? []; ?>
    <p><a href="/admin/m1">Back to M1 Suite</a></p>
    <h1>Saved ABHA Profiles</h1>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ABHA Number</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th>Verified At</th>
                    <th>Profile Snapshot</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $profile): ?>
                    <tr>
                        <td><?= esc((string) ($profile->abha_number ?? '')) ?></td>
                        <td><?= esc((string) ($profile->full_name ?? '')) ?></td>
                        <td><?= esc((string) ($profile->mobile ?? '')) ?></td>
                        <td><?= esc((string) ($profile->status ?? '')) ?></td>
                        <td><?= esc((string) ($profile->last_verified_at ?? '')) ?></td>
                        <td class="mono"><?= esc((string) ($profile->profile_json ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
