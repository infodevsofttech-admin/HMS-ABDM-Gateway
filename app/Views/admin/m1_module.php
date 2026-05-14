<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'M1 Module Interface'; ?>

<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-flask"></i> M1 Module Interface <small>Manual endpoint testing &amp; export</small></h3>
    </div>
</div>
<div class="clearfix"></div>

<style> background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .stack { display: grid; gap: 16px; }
        .grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        select, textarea, button { width: 100%; font: inherit; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; }
        textarea { min-height: 220px; font-family: Consolas, Monaco, monospace; }
        button { background: #2563eb; color: #fff; border: 0; cursor: pointer; font-weight: 600; }
        button:hover { background: #1d4ed8; }
        .note { color: #475569; font-size: 13px; }
        .ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 10px; border-radius: 8px; }
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px; border-radius: 8px; }
        .mono { white-space: pre-wrap; background: #0b1020; color: #cbd5e1; border-radius: 8px; padding: 12px; font-family: Consolas, Monaco, monospace; font-size: 13px; }
        .pill { display: inline-block; padding: 2px 10px; border-radius: 999px; background: #e2e8f0; font-size: 12px; font-weight: 700; }
        .pill.good { background: #dcfce7; color: #065f46; }
        .pill.bad { background: #fee2e2; color: #991b1b; }
        a { color: #1d4ed8; text-decoration: none; }
        code { background: #f3f4f6; padding: 2px 4px; border-radius: 4px; }
</style>

<div class="stack">
        <?php if (!empty($message)): ?>
            <div class="ok"><?= esc((string) $message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="err"><?= esc((string) $error) ?></div>
        <?php endif; ?>

        <div class="card">
            <p>This test console invokes gateway M1 endpoints from admin and stores each run in Test Logs.</p>
            <p class="note">Use test credentials/data from ABDM sandbox before switching production endpoints.</p>
        </div>

        <div class="card">
            <form method="post" action="/admin/m1-module/test">
                <?= csrf_field() ?>
                <div class="grid">
                    <div>
                        <label for="mode">Mode</label>
                        <select id="mode" name="mode" required>
                            <option value="sandbox">Sandbox</option>
                            <option value="live">Live</option>
                        </select>
                        <p class="note">Switch to Live only after full ABDM sandbox approval.</p>
                    </div>
                    <div>
                        <label for="token">Bearer Token</label>
                        <input type="password" id="token" name="token" autocomplete="off" placeholder="Paste Bearer token here" required />
                        <p class="note">Token is not saved; used only for this test run.</p>
                    </div>
                    <div>
                        <label for="endpoint_key">M1 Endpoint</label>
                        <select id="endpoint_key" name="endpoint_key" required onchange="fillSamplePayload()">
                            <option value="abha_validate" data-sample='{"abha_id":"11-1111-1111-1111"}'>ABHA Validate</option>
                            <option value="aadhaar_generate_otp" data-sample='{"aadhaar":"123412341234"}'>ABHA Aadhaar Generate OTP</option>
                            <option value="aadhaar_verify_otp" data-sample='{"txnId":"sample-txn-id","otp":"123456"}'>ABHA Aadhaar Verify OTP</option>
                            <option value="mobile_generate_otp" data-sample='{"mobile":"9999999999"}'>ABHA Mobile Generate OTP</option>
                            <option value="mobile_verify_otp" data-sample='{"txnId":"sample-txn-id","otp":"123456"}'>ABHA Mobile Verify OTP</option>
                            <option value="gateway_status" data-sample='{}'>Gateway Status</option>
                            <option value="health" data-sample='{}'>Health</option>
                        </select>
                        <p class="note">Select an endpoint to auto-fill a sample payload from ABDM M1 documentation.</p>
                    </div>
                    <div>
                        <label for="payload_json">Payload JSON</label>
                        <textarea id="payload_json" name="payload_json" placeholder='{"abha_id":"11-1111-1111-1111"}'></textarea>
                        <button type="button" onclick="fillSamplePayload()">Fill Sample</button>
                    </div>
                </div>
                <p><button type="submit">Run M1 Test</button></p>
            </form>
        </div>
        <div class="card">
            <form method="get" action="/admin/m1-module/export">
                <label for="export_format">Export Test Logs:</label>
                <select id="export_format" name="format">
                    <option value="csv">CSV</option>
                    <option value="json">JSON</option>
                </select>
                <button type="submit">Export</button>
            </form>
        </div>
        <script>
        function fillSamplePayload() {
            var sel = document.getElementById('endpoint_key');
            var opt = sel.options[sel.selectedIndex];
            var sample = opt.getAttribute('data-sample');
            if (sample) {
                document.getElementById('payload_json').value = sample;
            }
        }
        </script>

        <?php if (!empty($result) && is_array($result)): ?>
            <?php $isOk = isset($result['statusCode']) && (int) $result['statusCode'] >= 200 && (int) $result['statusCode'] < 300; ?>
            <div class="card">
                <p>
                    <strong>Last Run:</strong>
                    <span class="pill <?= $isOk ? 'good' : 'bad' ?>">HTTP <?= esc((string) ($result['statusCode'] ?? 'N/A')) ?></span>
                </p>
                <p><strong>Endpoint:</strong> <?= esc((string) ($result['method'] ?? '')) ?> <?= esc((string) ($result['path'] ?? '')) ?></p>
                <p><strong>Request JSON</strong></p>
                <div class="mono"><?= esc((string) ($result['requestJson'] ?? '{}')) ?></div>
                <p><strong>Response</strong></p>
                <div class="mono"><?= esc((string) ($result['responseBody'] ?? '')) ?></div>
            </div>
        <?php endif; ?>
    </div>
<?= $this->endSection() ?>
