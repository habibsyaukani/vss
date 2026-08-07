<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate request
$request = \Illuminate\Http\Request::create('/admin/speed/data', 'GET', [
    'start_date' => '2026-08-01',
    'end_date' => '2026-08-01',
    'location' => 'SELATAN',
    'speed_filter' => 'low'
]);

$controller = new \App\Http\Controllers\Frontend\SpeedController();
try {
    $response = $controller->data($request);
    echo "Success!\n";
    // echo $response->getContent();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
