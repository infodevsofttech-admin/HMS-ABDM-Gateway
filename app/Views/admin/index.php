<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registered Hospitals – HMS-ABDM Gateway</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <style>
        body { background: #f8f9fa; }
        .navbar-brand { font-weight: 700; }
        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .api-key-mono { font-family: monospace; font-size: .85em; word-break: break-all; }
        .table th { white-space: nowrap; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= base_url('admin') ?>">🏥 HMS-ABDM Gateway</a>
        <span class="navbar-text text-white-50 ms-2">Admin Panel</span>
        <div class="ms-auto">
            <a href="<?= base_url('admin/logout') ?>" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid px-4">

    <!-- Flash messages -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- New API key reveal (shown once after register / regenerate) -->
    <?php $newKey = session()->getFlashdata('new_api_key'); ?>
    <?php if ($newKey): ?>
        <div class="alert alert-warning border border-warning-subtle">
            <h6 class="fw-bold">⚠️ Copy this API Key now — it will not be shown again</h6>
            <p class="mb-1 text-muted small">Hospital: <strong><?= esc(session()->getFlashdata('new_hospital_name')) ?></strong></p>
            <div class="input-group mt-2">
                <input type="text" id="newKeyInput" class="form-control font-monospace"
                       value="<?= esc($newKey) ?>" readonly>
                <button class="btn btn-outline-secondary" type="button" id="copyKeyBtn">
                    Copy
                </button>
            </div>
            <p class="mt-2 mb-0 small text-muted">
                Set <code>ABDM_GATEWAY_API_KEY=<?= esc($newKey) ?></code> in the local HMS <code>.env</code>
                (or paste it into the HMS ABDM Settings page).
            </p>
        </div>
    <?php endif; ?>

    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0">Registered Hospitals</h5>
        <a href="<?= base_url('admin/register') ?>" class="btn btn-primary btn-sm ms-auto">
            + Register New Hospital
        </a>
    </div>

    <?php if (empty($facilities)): ?>
        <div class="card p-4 text-center text-muted">
            No hospitals registered yet. Click <strong>+ Register New Hospital</strong> to get started.
        </div>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Hospital Name</th>
                            <th>HMS ID</th>
                            <th>State / District</th>
                            <th>API Key (masked)</th>
                            <th>Status</th>
                            <th>Last Used</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facilities as $i => $f): ?>
                            <tr>
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <strong><?= esc($f['hospital_name']) ?></strong>
                                    <?php if ($f['contact_email']): ?>
                                        <br><small class="text-muted"><?= esc($f['contact_email']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= esc($f['hms_id']) ?></code></td>
                                <td>
                                    <?= esc($f['state'] ?? '—') ?><br>
                                    <small class="text-muted"><?= esc($f['district'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="api-key-mono text-secondary">
                                        <?= esc(substr($f['api_key'], 0, 8)) ?>…<?= esc(substr($f['api_key'], -4)) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($f['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= $f['last_used_at'] ? esc($f['last_used_at']) : 'Never' ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/toggle/' . $f['id']) ?>"
                                       class="btn btn-sm <?= $f['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                       title="<?= $f['is_active'] ? 'Disable' : 'Enable' ?>">
                                        <?= $f['is_active'] ? 'Disable' : 'Enable' ?>
                                    </a>
                                    <a href="<?= base_url('admin/regenerate/' . $f['id']) ?>"
                                       class="btn btn-sm btn-outline-secondary js-confirm"
                                       data-message="Regenerate API key for this facility? The old key will stop working immediately."
                                       title="Regenerate Key">
                                        Regen Key
                                    </a>
                                    <a href="<?= base_url('admin/delete/' . $f['id']) ?>"
                                       class="btn btn-sm btn-outline-danger js-confirm"
                                       data-message="Delete this facility? This cannot be undone."
                                       title="Delete">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Integration Guide -->
    <div class="card mt-4 p-4">
        <h6 class="fw-bold">📋 HMS Integration – Quick Reference</h6>
        <p class="text-muted small mb-2">
            The local HMS must send the API key in the <code>X-API-Key</code> HTTP header on every request to this gateway:
        </p>
        <pre class="bg-light p-3 rounded small mb-2">POST <?= base_url('sync/hospital') ?>

Content-Type: application/json
X-API-Key: &lt;your-api-key&gt;

{ "hms_id": "HOSP-001", "name": "City Hospital", ... }</pre>
        <p class="text-muted small mb-0">
            See <a href="<?= base_url() ?>">README</a> → HMS Integration section for the full PHP connector library and endpoint list.
        </p>
    </div>

</div>

<footer class="text-center text-muted small py-4 mt-5">
    HMS-ABDM Gateway &mdash; Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmOUgBSBkl41VJU+eoLMi5K/1CkA"
        crossorigin="anonymous"></script>
<script>
    // Clipboard copy button with fallback
    var copyBtn = document.getElementById('copyKeyBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var input = document.getElementById('newKeyInput');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(input.value).then(function () {
                    copyBtn.textContent = 'Copied!';
                }).catch(function () {
                    fallbackCopy(input, copyBtn);
                });
            } else {
                fallbackCopy(input, copyBtn);
            }
        });
    }
    function fallbackCopy(input, btn) {
        input.select();
        try {
            document.execCommand('copy');
            btn.textContent = 'Copied!';
        } catch (e) {
            btn.textContent = 'Select & copy manually';
        }
    }

    // Confirm dialogs via data-message (avoids inline JS XSS risk)
    document.querySelectorAll('.js-confirm').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!window.confirm(el.getAttribute('data-message'))) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>
