<?= $this->extend('layout/admin_layout') ?>
<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-user-md"></i> HPR Professionals</h3>
    </div>
</div>
<div class="clearfix"></div>

<?php if ($message = ($message ?? null)): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    <i class="fa fa-check-circle"></i> <?= esc($message) ?>
</div>
<?php endif; ?>
<?php if ($error = ($error ?? null)): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    <i class="fa fa-exclamation-circle"></i> <?= esc($error) ?>
</div>
<?php endif; ?>

<!-- Info Banner -->
<div class="alert alert-info" style="margin-bottom:20px;">
    <i class="fa fa-info-circle"></i>
    <strong>About HPR IDs:</strong>
    HPR (Health Professional Registry) is a separate ABDM registry for doctors, nurses, and allied health professionals.
    HPR IDs follow the format <code>name@hpr.abdm</code> (sandbox) or a 14-digit number.
    Professionals must register at <a href="https://nhpr.abdm.gov.in" target="_blank" rel="noopener">nhpr.abdm.gov.in</a>.
    <br><em>Note: HPR search/verify API is not included in ABDM M1 — HPR IDs are stored here for reference and used in OPD scan &amp; share flows.</em>
</div>

<!-- Hospital Selector -->
<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_content">
                <form method="get" action="/admin/hpr-professionals" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <label style="margin:0;font-weight:600;">Select Hospital:</label>
                    <select name="hospital_id" onchange="this.form.submit()" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;min-width:260px;">
                        <option value="">— Choose a hospital —</option>
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?= esc((string) $h->id) ?>" <?= ((int)($hospitalId ?? 0) === (int)$h->id) ? 'selected' : '' ?>>
                                <?= esc((string) $h->hospital_name) ?> (<?= esc((string) $h->hfr_id) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($hospital !== null): ?>

<!-- Add Professional Form -->
<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-plus-circle"></i> Add HPR Professional — <?= esc((string) $hospital->hospital_name) ?></h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="post" action="/admin/hpr-professionals/create">
                    <?= csrf_field() ?>
                    <input type="hidden" name="hospital_id" value="<?= esc((string) $hospital->id) ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Full Name <span style="color:red">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Dr. Rajesh Kumar" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>HPR ID <span style="color:red">*</span></label>
                                <input type="text" name="hpr_id" class="form-control" placeholder="rajesh.kumar@hpr.abdm" required>
                                <small class="text-muted">Format: <code>name@hpr.abdm</code> or 14-digit HPR number</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Designation</label>
                                <input type="text" name="designation" class="form-control" placeholder="Senior Physician">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group" style="position:relative;">
                                <label>Specialization <small class="text-muted" style="font-size:11px;">(SNOMED CT — multiple)</small></label>
                                <div id="snomed-spec-admin-tags" style="margin-bottom:6px;line-height:2;"></div>
                                <input type="text" id="snomed-spec-admin-input" class="form-control" placeholder="Type to search and add…" autocomplete="off">
                                <input type="hidden" name="specializations_json" id="snomed-spec-admin-hidden" value="[]">
                                <div id="snomed-spec-admin-drop" style="display:none;position:absolute;z-index:1000;width:100%;max-height:220px;overflow-y:auto;box-shadow:0 4px 8px rgba(0,0,0,.18);border:1px solid #ddd;border-radius:4px;background:#fff;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Department</label>
                                <input type="text" name="department" class="form-control" placeholder="OPD">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Medical Council Reg. Number</label>
                                <input type="text" name="registration_number" class="form-control" placeholder="MCI-12345">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add Professional</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Professionals List -->
<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-list"></i> Registered HPR Professionals
                    <small class="text-muted">(<?= count($professionals) ?> total)</small>
                </h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <?php if (empty($professionals)): ?>
                    <div class="alert alert-warning">
                        No HPR professionals registered for this hospital yet.
                    </div>
                <?php else: ?>
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>HPR ID</th>
                                <th>Designation</th>
                                <th>Specialization</th>
                                <th>Department</th>
                                <th>Reg. No.</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($professionals as $p): ?>
                                <tr>
                                    <td><?= esc((string) $p['id']) ?></td>
                                    <td><strong><?= esc((string) $p['name']) ?></strong></td>
                                    <td>
                                        <code><?= esc((string) $p['hpr_id']) ?></code>
                                        <a href="https://nhpr.abdm.gov.in" target="_blank" rel="noopener" title="Verify on HPR Portal"
                                           style="margin-left:6px;color:#7f8c8d;font-size:11px;">
                                            <i class="fa fa-external-link"></i>
                                        </a>
                                    </td>
                                    <td><?= esc((string) ($p['designation'] ?? '—')) ?></td>
                                    <td>
                                        <?php
                                            $_specRaw = (string) ($p['specialization'] ?? '');
                                            $_specs   = ($_specRaw !== '' && $_specRaw[0] === '[') ? (json_decode($_specRaw, true) ?: []) : ($_specRaw ? [['term' => $_specRaw, 'code' => $p['specialization_code'] ?? '']] : []);
                                        ?>
                                        <?php if ($_specs): foreach ($_specs as $_s): ?>
                                            <span style="display:inline-block;margin:1px 2px;padding:2px 8px;border-radius:10px;background:#dbeafe;color:#1e40af;font-size:11px;font-weight:normal;">
                                                <?= esc((string) ($_s['term'] ?? '')) ?>
                                                <?php if (!empty($_s['code'])): ?><span style="opacity:.65;">[<?= esc((string) $_s['code']) ?>]</span><?php endif; ?>
                                            </span>
                                        <?php endforeach; else: ?>—<?php endif; ?>
                                    </td>
                                    <td><?= esc((string) ($p['department'] ?? '—')) ?></td>
                                    <td><?= esc((string) ($p['registration_number'] ?? '—')) ?></td>
                                    <td>
                                        <?php if ($p['is_active']): ?>
                                            <span class="label label-success">Active</span>
                                        <?php else: ?>
                                            <span class="label label-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        <form method="post" action="/admin/hpr-professionals/<?= esc((string) $p['id']) ?>/toggle" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-xs <?= $p['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                                <?= $p['is_active'] ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                        &nbsp;
                                        <form method="post" action="/admin/hpr-professionals/<?= esc((string) $p['id']) ?>/delete" style="display:inline;"
                                              onsubmit="return confirm('Remove <?= esc(addslashes((string) $p['name'])) ?>?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-xs btn-danger">
                                                <i class="fa fa-trash"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info"><i class="fa fa-arrow-up"></i> Select a hospital above to manage its HPR professionals.</div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
.snomed-ac-item { display:block;padding:8px 12px;font-size:12px;color:#333;text-decoration:none; }
.snomed-ac-item:hover, .snomed-ac-focused { background:#e8f0fe; }
.snomed-chip { display:inline-flex;align-items:center;gap:5px;margin:2px 4px 2px 0;padding:3px 10px;border-radius:12px;background:#dbeafe;color:#1e40af;font-size:12px; }
.snomed-chip-rm { color:#1e40af;text-decoration:none;font-weight:bold;font-size:14px;line-height:1; }
.snomed-chip-rm:hover { color:#991b1b; }
</style>
<script>
(function () {
    function initSnomed(pfx) {
        var inp    = document.getElementById(pfx + '-input');
        var drop   = document.getElementById(pfx + '-drop');
        var tagsEl = document.getElementById(pfx + '-tags');
        var hidden = document.getElementById(pfx + '-hidden');
        if (!inp) return;
        var specs   = [];
        var focused = -1;
        var timer;
        var CSNOTK = 'https://csnotk.e-atria.in/api/search/search?state=active&semantictag=qualifier+value&acceptability=preferred&returnlimit=15&groupbyconcept=true&term=';

        function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

        function renderTags() {
            tagsEl.innerHTML = '';
            specs.forEach(function (s, i) {
                var span = document.createElement('span');
                span.className = 'snomed-chip';
                span.innerHTML = esc(s.term);
                if (s.code) span.innerHTML += ' <small style="opacity:.65">[' + esc(s.code) + ']</small>';
                var rm = document.createElement('a');
                rm.href = '#'; rm.className = 'snomed-chip-rm'; rm.innerHTML = '&times;';
                rm.setAttribute('data-i', i);
                rm.addEventListener('click', function (e) {
                    e.preventDefault();
                    specs.splice(+this.getAttribute('data-i'), 1);
                    renderTags(); saveHidden();
                });
                span.appendChild(rm);
                tagsEl.appendChild(span);
            });
        }

        function saveHidden() { hidden.value = JSON.stringify(specs); }
        function getItems()   { return drop.querySelectorAll('.snomed-ac-item'); }

        function setFocused(idx) {
            var items = getItems();
            items.forEach(function (el, i) { el.classList.toggle('snomed-ac-focused', i === idx); });
            focused = idx;
            if (idx >= 0 && items[idx]) items[idx].scrollIntoView({block:'nearest'});
        }

        function doSelect(term, code) {
            if (code && specs.some(function (s) { return s.code === code; })) {
                inp.value = ''; drop.style.display = 'none'; return;
            }
            specs.push({term: term, code: code});
            renderTags(); saveHidden();
            inp.value = ''; drop.style.display = 'none'; focused = -1;
        }

        function renderDrop(data) {
            drop.innerHTML = ''; focused = -1;
            if (!Array.isArray(data) || !data.length) { drop.style.display = 'none'; return; }
            data.forEach(function (item) {
                var a = document.createElement('a');
                a.href = '#'; a.className = 'snomed-ac-item';
                a.innerHTML = esc(item.term) + ' <span style="color:#9ca3af;font-size:11px;">[' + esc(item.conceptId) + ']</span>';
                a.setAttribute('data-term', item.term);
                a.setAttribute('data-code', item.conceptId);
                a.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    doSelect(this.getAttribute('data-term'), this.getAttribute('data-code'));
                });
                drop.appendChild(a);
            });
            drop.style.display = 'block';
        }

        inp.addEventListener('keydown', function (e) {
            if (drop.style.display === 'none') return;
            var items = getItems();
            if (!items.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault(); setFocused(Math.min(focused + 1, items.length - 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault(); setFocused(Math.max(focused - 1, 0));
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (focused >= 0 && items[focused]) doSelect(items[focused].getAttribute('data-term'), items[focused].getAttribute('data-code'));
            } else if (e.key === 'Escape') {
                drop.style.display = 'none'; focused = -1;
            }
        });

        inp.addEventListener('input', function () {
            var term = inp.value.trim();
            clearTimeout(timer);
            if (term.length < 2) { drop.style.display = 'none'; return; }
            timer = setTimeout(function () {
                fetch(CSNOTK + encodeURIComponent(term))
                .then(function (r) { return r.json(); })
                .then(renderDrop)
                .catch(function () { drop.style.display = 'none'; });
            }, 300);
        });

        document.addEventListener('click', function (e) {
            if (!drop.contains(e.target) && e.target !== inp) { drop.style.display = 'none'; focused = -1; }
        });
    }

    initSnomed('snomed-spec-admin');
}());
</script>
<?= $this->endSection() ?>
