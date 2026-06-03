<?php
/**
 * Regenerate alarm data dengan mapping yang benar
 * Run: php regenerate_data.php
 */

// Bootstrap Laravel
$basePath = __DIR__;
require $basePath . '/vendor/autoload.php';

$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Menggunakan command yang sudah dibuat
$exitCode = $kernel->call('command:regenerate-data', []);

exit($exitCode);
