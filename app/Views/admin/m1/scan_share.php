<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scan &amp; Share Queue - M1 Module</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; color: #111827; }
        h1 { margin-bottom: 4px; }
        .subtitle { color: #6b7280; font-size: 13px; margin-bottom: 20px; }
        .card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
        .toolbar form { display: flex; gap: 8px; align-items: center; }
        .toolbar input[type=date] { padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 6px; font: inherit; font-size: 13px; }
        .toolbar button { padding: 7px 16px; background: #2563eb; color: #fff; border: 0; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .toolbar button:hover { background: #1d4ed8; }
        .toolbar a.btn { padding: 7px 16px; background: #f3f4f6; color: #374151; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f9fafb; padding: 10px 12px; text-align: left; border-bottom: 2px solid #e5e7eb; color: #6b7280; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        tr:last-child td { border-bottom: 0; }
        .token-num { font-size: 22px; font-weight: 900; color: #2563eb; text-align: center; line-height: 1; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-pending  { background: #fef9c3; color: #713f12; }
        .badge-done     { background: #d1fae5; color: #065f46; }
        .abha { font-family: monospace; font-size: 12px; color: #7c3aed; }
        .muted { color: #9ca3af; font-size: 12px; }
        .empty { text-align: center; color: #9ca3af; padding: 40px; font-size: 15px; }
        .count-box { display: flex; gap: 20px; margin-bottom: 16px; }
        .count-box div { background: #fff; border-radius: 8px; padding: 12px 20px; box-shadow: 0 1px 3px rgba(0,0,0,.07); text-align: center; }
        .count-box .n { font-size: 28px; font-weight: 800; color: #2563eb; }
        .count-box .l { font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; }
        a { color: #2563eb; text-decoration: none; }
        @media print {
            .toolbar, .no-print { display: none; }
            body { margin: 0; background: #fff; }
        }
    </style>
</head>
<body>
    <?php
    $tokens = $tokens ?? [];
    $date   = $date ?? date('Y-m-d');
    $total  = count($tokens);
    $served = count(array_filter($tokens, fn($t) => ($t->status ?? '') === 'SERVED'));
    ?>

    <p><a href="/admin/m1">← Back to M1 Suite</a></p>
    <h1>Scan &amp; Share — OPD Token Queue</h1>
    <p class="subtitle">Patients who scanned the facility QR in their ABHA app today appear here.</p>

    <div class="toolbar">
        <form method="get">
            <label style="font-size:13px;font-weight:600;">Date:</label>
            <input type="date" name="date" value="<?= esc($date) ?>" max="<?= date('Y-m-d') ?>">
            <button type="submit">Filter</button>
        </form>
        <a class="btn no-print" href="/admin/m1/scan-share-setup">⚙ Setup / Register HIP</a>
        <a class="btn no-print" href="#" onclick="window.print();return false;">🖨 Print</a>
    </div>

    <div class="count-box">
        <div><div class="n"><?= $total ?></div><div class="l">Total Today</div></div>
        <div><div class="n"><?= $served ?></div><div class="l">Served</div></div>
        <div><div class="n"><?= $total - $served ?></div><div class="l">Pending</div></div>
    </div>

    <div class="card">
        <?php if (empty($tokens)): ?>
            <div class="empty">No patients have scanned the facility QR on <?= esc($date) ?>.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Token</th>
                    <th>Patient</th>
                    <th>ABHA Number</th>
                    <th>Gender / DOB</th>
                    <th>Phone</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tokens as $t): ?>
                <tr>
                    <td><div class="token-num"><?= esc((string) ($t->token_number ?? '—')) ?></div></td>
                    <td>
                        <strong><?= esc((string) ($t->patient_name ?: 'Unknown')) ?></strong><br>
                        <?php if (!empty($t->abha_address)): ?>
                            <span class="muted"><?= esc((string) $t->abha_address) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="abha"><?= esc((string) ($t->abha_number ?: '—')) ?></span></td>
                    <td>
                        <?= esc((string) ($t->gender ?: '—')) ?>
                        <?php
                        $dob = array_filter([(string)($t->day_of_birth??''), (string)($t->month_of_birth??''), (string)($t->year_of_birth??'')]);
                        if (!empty($dob)):
                        ?><br><span class="muted"><?= esc(implode('/', $dob)) ?></span><?php endif; ?>
                    </td>
                    <td><?= esc((string) ($t->phone ?: '—')) ?></td>
                    <td>
                        <span class="muted"><?= esc(substr((string)($t->created_at ?? ''), 11, 5)) ?></span>
                        <?php if (empty($t->on_share_sent)): ?>
                            <br><span style="font-size:10px;color:#ef4444;">on-share pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= ($t->status ?? '') === 'SERVED' ? 'badge-done' : 'badge-pending' ?>">
                            <?= esc((string) ($t->status ?? 'PENDING')) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh every 30s if viewing today
        const today = new Date().toISOString().slice(0,10);
        if ('<?= esc($date) ?>' === today) {
            setTimeout(() => location.reload(), 30000);
        }
    </script>
</body>
</html>
