<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – HMS-ABDM Gateway</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <style>
        body { background: #f0f4f8; }
        .login-card { max-width: 420px; margin: 8vh auto; border: none; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
    </style>
</head>
<body>
<div class="container">
    <div class="card login-card p-4">
        <h4 class="text-center mb-1">🏥 HMS-ABDM Gateway</h4>
        <p class="text-center text-muted mb-4">Admin Panel Login</p>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('admin/login') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="token" class="form-label fw-semibold">Admin Token</label>
                <input type="password" class="form-control" id="token" name="token"
                       placeholder="Enter GATEWAY_ADMIN_TOKEN" required autofocus>
                <div class="form-text">Set <code>GATEWAY_ADMIN_TOKEN</code> in your <code>.env</code> file.</div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
