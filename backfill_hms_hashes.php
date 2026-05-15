<?php
/**
 * One-time backfill: compute hms_api_key_hash for all hms_credentials rows
 * that have an hms_api_key but no hms_api_key_hash.
 *
 * Run on the server:  php backfill_hms_hashes.php
 * No CI4 bootstrap needed — uses PDO directly with .env credentials.
 */

// ── Parse .env ───────────────────────────────────────────────────────────────
$env = [];
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) { die("ERROR: .env not found at $envFile\n"); }
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) { continue; }
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $env[$k] = trim($v, '"\'');
}

$host   = $env['database.default.hostname'] ?? $env['DB_HOST']     ?? '127.0.0.1';
$db     = $env['database.default.database'] ?? $env['DB_DATABASE'] ?? '';
$user   = $env['database.default.username'] ?? $env['DB_USER']     ?? '';
$pass   = $env['database.default.password'] ?? $env['DB_PASS']     ?? '';
$port   = $env['database.default.port']     ?? $env['DB_PORT']     ?? '3306';

if (!$db || !$user) { die("ERROR: Could not read DB credentials from .env\n"); }

// ── Connect ──────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: DB connection failed: " . $e->getMessage() . "\n");
}

// ── Backfill with a single UPDATE using MySQL's built-ins ────────────────────
// encryptCredential stores:  base64_encode( plainKey . ':' . hmac )
// decryptCredential returns: $parts[0] of explode(':', base64_decode(...))
// MySQL equivalent:          SUBSTRING_INDEX(FROM_BASE64(hms_api_key), ':', 1)
// SHA256:                    SHA2(..., 256)

$sql = "
    UPDATE hms_credentials
    SET    hms_api_key_hash = SHA2(SUBSTRING_INDEX(FROM_BASE64(hms_api_key), ':', 1), 256)
    WHERE  hms_api_key IS NOT NULL
      AND  hms_api_key != ''
      AND  (hms_api_key_hash IS NULL OR hms_api_key_hash = '')
";

$affected = $pdo->exec($sql);
echo "Done. Rows updated: $affected\n";

// ── Verify ───────────────────────────────────────────────────────────────────
$rows = $pdo->query("SELECT id, hms_name, LEFT(hms_api_key_hash,16) AS hash_prefix
                     FROM hms_credentials
                     WHERE hms_api_key_hash IS NOT NULL AND hms_api_key_hash != ''")
            ->fetchAll(PDO::FETCH_ASSOC);
echo "\nAll credentialed rows now:\n";
foreach ($rows as $r) {
    printf("  id=%-4s  %-30s  hash_prefix=%s...\n", $r['id'], $r['hms_name'], $r['hash_prefix']);
}

