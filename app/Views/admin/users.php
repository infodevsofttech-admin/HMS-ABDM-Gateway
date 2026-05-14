<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hospital Users - ABDM Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; }
        .panel { background: #fff; padding: 16px; border-radius: 10px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .row { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 10px; }
        input, select, button { padding: 8px; width: 100%; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #fef3c7; }
        .ok { color: #065f46; }
        .err { color: #b91c1c; }
        a { color: #1d4ed8; text-decoration: none; }
    </style>
</head>
<body>
    <p><a href="/admin/dashboard">Back to Dashboard</a></p>
    <h1>Hospital Users</h1>

    <?php if (!empty($message)): ?><p class="ok"><?= esc($message) ?></p><?php endif; ?>
    <?php if (!empty($error)): ?><p class="err"><?= esc($error) ?></p><?php endif; ?>

    <div class="panel">
        <h2>Create User Credential</h2>
        <form method="post" action="/admin/users/create">
            <div class="row">
                <select name="hospital_id" required>
                    <option value="">Select Hospital</option>
                    <?php foreach ($hospitals as $hospital): ?>
                        <option value="<?= esc((string) $hospital->id) ?>"><?= esc((string) $hospital->hospital_name) ?> (<?= esc((string) $hospital->hfr_id) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input name="username" placeholder="Username" required>
                <input name="password" type="password" placeholder="Password (min 8 chars)" required>
            </div>
            <p><button type="submit">Create User + API Token</button></p>
        </form>
    </div>

    <div class="panel">
        <h2>User List</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Hospital</th>
                    <th>HFR ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= esc((string) $user->id) ?></td>
                        <td><?= esc((string) ($user->hospital_name ?? '')) ?></td>
                        <td><?= esc((string) ($user->hfr_id ?? '')) ?></td>
                        <td><?= esc((string) $user->username) ?></td>
                        <td><?= esc((string) $user->role) ?></td>
                        <td><?= (int) $user->is_active === 1 ? 'Active' : 'Inactive' ?></td>
                        <td><?= esc((string) $user->created_at) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
