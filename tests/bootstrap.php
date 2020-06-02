<?php
$projectDir = dirname(__DIR__);
$vendor = "{$projectDir}/vendor/";

if (!file_exists($vendor . 'autoload.php')) {
    die('Please install via Composer before running tests.');
}

require_once __DIR__ . '/stubs.php';
require_once $vendor . 'autoload.php';

unset($vendor, $projectDir);
