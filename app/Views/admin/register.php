<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Hospital – HMS-ABDM Gateway</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <style>
        body { background: #f8f9fa; }
        .navbar-brand { font-weight: 700; }
        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= base_url('admin') ?>">🏥 HMS-ABDM Gateway</a>
        <span class="navbar-text text-white-50 ms-2">Admin Panel</span>
        <div class="ms-auto">
            <a href="<?= base_url('admin') ?>" class="btn btn-sm btn-outline-light me-2">← Back</a>
            <a href="<?= base_url('admin/logout') ?>" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container" style="max-width:640px">

    <h5 class="mb-3">Register New Hospital / HMS Instance</h5>

    <?php if (session()->getFlashdata('errors') || isset($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ((session()->getFlashdata('errors') ?: $errors) as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card p-4">
        <form method="post" action="<?= base_url('admin/register') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="hospital_name" class="form-label fw-semibold">
                    Hospital / Facility Name <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="hospital_name" name="hospital_name"
                       value="<?= esc(old('hospital_name')) ?>"
                       placeholder="e.g. City General Hospital" required>
            </div>

            <div class="mb-3">
                <label for="hms_id" class="form-label fw-semibold">
                    HMS ID <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="hms_id" name="hms_id"
                       value="<?= esc(old('hms_id')) ?>"
                       placeholder="e.g. HOSP-001 (must match hms_id sent in sync payloads)" required>
                <div class="form-text">
                    This ID must exactly match the <code>hms_id</code> field your local HMS sends in every sync request.
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="state" class="form-label">State</label>
                    <input type="text" class="form-control" id="state" name="state"
                           value="<?= esc(old('state')) ?>" placeholder="Maharashtra">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="district" class="form-label">District</label>
                    <input type="text" class="form-control" id="district" name="district"
                           value="<?= esc(old('district')) ?>" placeholder="Pune">
                </div>
            </div>

            <div class="mb-3">
                <label for="contact_email" class="form-label">Contact Email</label>
                <input type="email" class="form-control" id="contact_email" name="contact_email"
                       value="<?= esc(old('contact_email')) ?>" placeholder="admin@hospital.in">
            </div>

            <div class="mb-4">
                <label for="contact_phone" class="form-label">Contact Phone</label>
                <input type="text" class="form-control" id="contact_phone" name="contact_phone"
                       value="<?= esc(old('contact_phone')) ?>" placeholder="9876543210">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Register &amp; Generate API Key</button>
                <a href="<?= base_url('admin') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="card mt-3 p-3 bg-light border-0">
        <p class="small text-muted mb-0">
            <strong>After registration:</strong> A unique 64-character API key will be generated and displayed <em>once</em>.
            Store it securely and paste it into the local HMS configuration
            (<code>ABDM_GATEWAY_API_KEY</code> in <code>.env</code>).
        </p>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmOUgBSBkl41VJU+eoLMi5K/1CkA"
        crossorigin="anonymous"></script>
</body>
</html>
