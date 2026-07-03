<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing getCleanupStats() method...\n\n";

$controller = new \App\Http\Controllers\SystemControlController();

// Use reflection to call private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('getCleanupStats');
$method->setAccessible(true);

echo "Calling getCleanupStats()...\n";
$start = microtime(true);

try {
    $stats = $method->invoke($controller);
    $time = round((microtime(true) - $start) * 1000, 2);
    
    echo "\n✅ Success in {$time}ms!\n\n";
    echo "Results:\n";
    print_r($stats);
    
    if ($time > 5000) {
        echo "\n⚠️ WARNING: Query took > 5 seconds. This will cause page hang!\n";
    } else {
        echo "\n✅ Query is fast enough!\n";
    }
    
} catch (\Exception $e) {
    $time = round((microtime(true) - $start) * 1000, 2);
    echo "\n❌ ERROR after {$time}ms: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
