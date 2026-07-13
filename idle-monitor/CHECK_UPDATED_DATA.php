<?php
/**
 * Quick check: Verify updated data
 */

$pdo = new PDO("mysql:host=127.0.0.1;dbname=vss", "root", "");

// Sample devices dari berbagai series
$samples = [
    'GPE-B-806',
    'GPE-DT-1000', 
    'GPE-EX-2201',
    'GPE-GR-301',
    'GPE-HD-401',
    'GPE-WL-1101',
    'GPE-WT-801'
];

echo "========================================\n";
echo "VERIFIKASI DATA YANG SUDAH DIUPDATE\n";
echo "========================================\n\n";

$placeholders = str_repeat('?,', count($samples) - 1) . '?';
$stmt = $pdo->prepare("
    SELECT device_name, unit_code, location, series, updated_at 
    FROM devices 
    WHERE device_name IN ($placeholders)
    ORDER BY device_name
");
$stmt->execute($samples);

$found = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $found++;
    echo "Device: " . $row['device_name'] . "\n";
    echo "  Unit Code : " . ($row['unit_code'] ?? 'NULL') . "\n";
    echo "  Location  : " . $row['location'] . "\n";
    echo "  Series    : " . $row['series'] . "\n";
    echo "  Updated   : " . $row['updated_at'] . "\n";
    echo "\n";
}

echo "========================================\n";
echo "Total sample ditemukan: {$found} / " . count($samples) . "\n";
echo "========================================\n";
