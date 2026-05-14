<?php

use CodeIgniter\Boot;
use Config\Paths;

define('ENVIRONMENT', getenv('CI_ENVIRONMENT') ?: 'production');

if (ENVIRONMENT === 'development') {
    error_reporting(-1);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
}

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

$projectRoot = realpath(FCPATH . '..') ?: dirname(FCPATH);
$writablePath = $projectRoot . DIRECTORY_SEPARATOR . 'writable';

if (! is_dir($writablePath)) {
    @mkdir($writablePath, 0775, true);
}

foreach (['cache', 'logs', 'session', 'uploads', 'debugbar', 'tmp'] as $subDir) {
    $path = $writablePath . DIRECTORY_SEPARATOR . $subDir;
    if (! is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

require FCPATH . '../app/Config/Paths.php';

$paths = new Paths();

require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
