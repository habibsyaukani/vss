<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\HowenDeviceService;

echo "\n=== RESET AND SYNC DEVICES ===\n\n";

echo "[1] Clearing devices table...\n";
DB::table('devices')->truncate();
echo "✅ Cleared\n\n";

echo "[2] Syncing devices from Howen API...\n";
try {
    $service = new HowenDeviceService();
    $synced = $service->syncDevices();
    echo "✅ Synced {$synced} devices\n\n";
} catch (\Exception $e) {
    echo "❌ Error: {$e->getMessage()}\n\n";
}

echo "[3] Devices in database:\n";
DB::table('devices')->get()->each(function($d) {
    echo "  {$d->device_id}: {$d->device_name}\n";
});

echo "\n";
