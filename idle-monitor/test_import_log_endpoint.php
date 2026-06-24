<?php
/**
 * Quick test script for Import Log endpoint
 * Run: php test_import_log_endpoint.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Import Log Data Endpoint\n";
echo "================================\n\n";

// Test 1: Check database
echo "1. Database Check:\n";
$count = App\Models\ImportLog::count();
echo "   Total records: {$count}\n\n";

if ($count === 0) {
    echo "   ❌ NO DATA in import_logs table!\n";
    exit(1);
}

// Test 2: Get sample data
echo "2. Sample Data (latest 3):\n";
$samples = App\Models\ImportLog::orderBy('created_at', 'desc')->limit(3)->get();
foreach ($samples as $log) {
    echo "   - {$log->job_name} | {$log->status} | {$log->total_record} records\n";
}
echo "\n";

// Test 3: Test DataTables query
echo "3. Testing DataTables Query:\n";
try {
    $query = App\Models\ImportLog::query()->orderBy('created_at', 'desc');
    $result = Yajra\DataTables\Facades\DataTables::eloquent($query)
        ->addColumn('status_badge', function ($log) {
            return '<span class="badge">' . $log->status . '</span>';
        })
        ->toJson();
    
    $json = json_decode($result, true);
    echo "   Total records in response: " . $json['recordsTotal'] . "\n";
    echo "   Filtered records: " . $json['recordsFiltered'] . "\n";
    echo "   Data rows returned: " . count($json['data']) . "\n";
    echo "   ✅ DataTables query works!\n\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n\n";
}

echo "================================\n";
echo "Test completed!\n";
