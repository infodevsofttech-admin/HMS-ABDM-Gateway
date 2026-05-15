<?php
/**
 * One-time backfill: compute hms_api_key_hash for all hms_credentials rows
 * that have an hms_api_key but no hms_api_key_hash.
 *
 * Run on the server:  php backfill_hms_hashes.php
 */

// Bootstrap CodeIgniter 4
define('FCPATH', __DIR__ . '/public/');
chdir(__DIR__);
require_once 'vendor/autoload.php';
require_once 'app/Config/Paths.php';
$paths = new Config\Paths();
define('ROOTPATH', realpath($paths->rootDirectory) . DIRECTORY_SEPARATOR);
define('APPPATH',  realpath($paths->appDirectory)  . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath($paths->writableDirectory) . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath($paths->systemDirectory) . DIRECTORY_SEPARATOR);
define('PUBPATH', realpath($paths->publicDirectory) . DIRECTORY_SEPARATOR);
define('CIPATH', realpath(SYSTEMPATH . '../') . DIRECTORY_SEPARATOR);
define('CI_DEBUG', 0);
define('ENVIRONMENT', 'production');
require_once APPPATH . 'Config/Constants.php';

// Minimal CI4 bootstrap to get DB
$app = \Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();

// Decrypt helper: same logic as Hospital::decryptCredential()
$decrypt = static function (string $data): string {
    $decoded = base64_decode($data, true);
    if ($decoded === false) { return ''; }
    $parts = explode(':', $decoded);
    return $parts[0] ?? '';
};

// Fetch all rows with a key but no hash
$rows = $db->table('hms_credentials')
    ->select('id, hms_api_key')
    ->where('hms_api_key IS NOT NULL', null, false)
    ->where('(hms_api_key_hash IS NULL OR hms_api_key_hash = \'\')', null, false)
    ->get()
    ->getResultArray();

if (empty($rows)) {
    echo "Nothing to backfill — all rows already have hms_api_key_hash.\n";
    exit(0);
}

$updated = 0;
foreach ($rows as $row) {
    $plain = $decrypt($row['hms_api_key']);
    if ($plain === '') {
        echo "  [SKIP] id={$row['id']} — could not decrypt key\n";
        continue;
    }
    $hash = hash('sha256', $plain);
    $db->table('hms_credentials')
        ->where('id', $row['id'])
        ->update(['hms_api_key_hash' => $hash]);
    echo "  [OK]   id={$row['id']} — hash={$hash}\n";
    $updated++;
}

echo "\nDone. Updated $updated row(s).\n";
