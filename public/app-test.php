<?php

header('Content-Type: application/json');

$projectRoot = dirname(__DIR__);
$vendorAutoload = $projectRoot . '/vendor/autoload.php';
$pathsConfig = $projectRoot . '/app/Config/Paths.php';
$writable = $projectRoot . '/writable';

$checks = [
    'php_version' => PHP_VERSION,
    'time' => date('c'),
    'project_root' => $projectRoot,
    'vendor_autoload_exists' => file_exists($vendorAutoload),
    'paths_config_exists' => file_exists($pathsConfig),
    'writable_exists' => is_dir($writable),
    'writable_is_writable' => is_writable($writable),
];

$status = 200;
if (!$checks['vendor_autoload_exists'] || !$checks['paths_config_exists']) {
    $status = 500;
}

http_response_code($status);
echo json_encode([
    'ok' => $status === 200 ? 1 : 0,
    'message' => $status === 200 ? 'App test probe passed' : 'App test probe failed',
    'checks' => $checks,
], JSON_PRETTY_PRINT);
