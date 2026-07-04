<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logs = DB::table('system_logs')
    ->where('action', 'like', '%CLEANUP%')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

foreach($logs as $log) {
    echo "[{$log->created_at}] {$log->action}: {$log->message}\n";
    if (!empty($log->details)) {
        echo "  Details: " . substr($log->details, 0, 500) . "\n";
    }
}
