<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMS-ABDM Gateway – Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <style>
        body { background: #f8f9fa; }
        .navbar-brand { font-weight: 700; letter-spacing: .5px; }
        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .api-key-badge { font-family: monospace; font-size: .85em; word-break: break-all; }
        .table th { white-space: nowrap; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?= base_url('admin') ?>">🏥 HMS-ABDM Gateway</a>
        <span class="navbar-text text-white-50 ms-3">Admin Panel</span>
        <div class="ms-auto">
            <a href="<?= base_url('admin/logout') ?>" class="btn btn-sm btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <?= $this->renderSection('content') ?>
</div>

<footer class="text-center text-muted small py-4 mt-5">
    HMS-ABDM Gateway &mdash; Admin Panel
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc4s9bIOgUxi8T/jzmOUgBSBkl41VJU+eoLMi5K/1CkA"
        crossorigin="anonymous"></script>
</body>
</html>
