<?php
/**
 * Script untuk update series VOLVO dan location M.SERVICE
 * Berdasarkan gambar yang diberikan
 */

// File CSV input dan output
$inputFile = __DIR__ . '/devices_update_data.csv';
$outputFile = __DIR__ . '/devices_update_data.csv';
$backupFile = __DIR__ . '/devices_update_data_backup_' . date('YmdHis') . '.csv';

echo "===========================================\n";
echo "UPDATE VOLVO SERIES & M.SERVICE LOCATION\n";
echo "===========================================\n\n";

// Backup file original
if (file_exists($inputFile)) {
    copy($inputFile, $backupFile);
    echo "✅ Backup created: " . basename($backupFile) . "\n\n";
}

// Unit codes dari Gambar 1 - untuk update series ke VOLVO
$volvoUnits = [
    'GPE932', 'GPE937', 'GPE951', 'GPE952', 'GPE953', 'GPE955', 
    'GPE998', 'GPE999', 'GPE1000', 'GPE1001', 'GPE1002', 'GPE1003',
    'GPE1005', 'GPE1006', 'GPE1007', 'GPE1008'
];

// Unit codes dari Gambar 2 - untuk update location ke M.SERVICE
// Mapping dari device code GPE-DT-28xx ke unit_code GPE11xx
$mserviceUnits = [
    'GPE1105', // GPE-DT-2801
    'GPE1106', // GPE-DT-2802
    'GPE1108', // GPE-DT-2803
    'GPE1109', // GPE-DT-2805
    'GPE1110', // GPE-DT-2806
    'GPE1112', // GPE-DT-2807
    'GPE1113', // GPE-DT-2808
    'GPE1125', // GPE-DT-2809
    'GPE1126', // GPE-DT-2810
    'GPE1127', // GPE-DT-2811
    'GPE1128'  // GPE-DT-2812
];

// Baca CSV
$rows = [];
$header = [];
$handle = fopen($inputFile, 'r');

if ($handle !== false) {
    $lineNumber = 0;
    while (($data = fgetcsv($handle)) !== false) {
        $lineNumber++;
        if ($lineNumber === 1) {
            $header = $data;
            $rows[] = $data;
        } else {
            $rows[] = $data;
        }
    }
    fclose($handle);
}

echo "Total devices: " . (count($rows) - 1) . "\n\n";

// Counters
$volvoUpdates = 0;
$mserviceUpdates = 0;
$volvoList = [];
$mserviceList = [];

// Process data
for ($i = 1; $i < count($rows); $i++) {
    $deviceCode = $rows[$i][0];
    $unitCode = $rows[$i][1];
    $series = $rows[$i][2];
    $location = $rows[$i][3];
    
    $updated = false;
    
    // Check untuk VOLVO series update
    if (in_array($unitCode, $volvoUnits)) {
        $oldSeries = $series;
        $rows[$i][2] = 'VOLVO';
        $volvoUpdates++;
        $volvoList[] = "$deviceCode ($unitCode): $oldSeries → VOLVO";
        $updated = true;
    }
    
    // Check untuk M.SERVICE location update
    if (in_array($unitCode, $mserviceUnits)) {
        $oldLocation = $location;
        $rows[$i][3] = 'M.SERVICE';
        $mserviceUpdates++;
        $mserviceList[] = "$deviceCode ($unitCode): $oldLocation → M.SERVICE";
        $updated = true;
    }
}

// Write updated CSV
$handle = fopen($outputFile, 'w');
foreach ($rows as $row) {
    fputcsv($handle, $row);
}
fclose($handle);

// Display results
echo "📊 UPDATE SUMMARY\n";
echo "===========================================\n";
echo "🔵 VOLVO Series Updates: $volvoUpdates devices\n";
echo "🔵 M.SERVICE Location Updates: $mserviceUpdates devices\n";
echo "✅ Total Updates: " . ($volvoUpdates + $mserviceUpdates) . "\n\n";

if (!empty($volvoList)) {
    echo "📋 VOLVO SERIES UPDATES:\n";
    echo "-------------------------------------------\n";
    foreach ($volvoList as $item) {
        echo "  • $item\n";
    }
    echo "\n";
}

if (!empty($mserviceList)) {
    echo "📋 M.SERVICE LOCATION UPDATES:\n";
    echo "-------------------------------------------\n";
    foreach ($mserviceList as $item) {
        echo "  • $item\n";
    }
    echo "\n";
}

echo "===========================================\n";
echo "✅ CSV file updated successfully!\n";
echo "📁 File: devices_update_data.csv\n";
echo "💾 Backup: " . basename($backupFile) . "\n";
echo "===========================================\n";

?>
