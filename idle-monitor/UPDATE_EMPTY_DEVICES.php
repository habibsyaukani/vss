<?php
/**
 * =====================================================
 * UPDATE DEVICES YANG KOSONG (NULL)
 * =====================================================
 * Script ini akan update devices yang unit_code, location, 
 * atau series-nya masih kosong/NULL
 * =====================================================
 */

$pdo = new PDO("mysql:host=127.0.0.1;dbname=vss;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "✅ Connected to database: vss\n\n";

// Data devices yang perlu diupdate
$rawData = file_get_contents(__DIR__ . '/DATA_397_DEVICES_REAL.txt');

if (!$rawData) {
    die("❌ File DATA_397_DEVICES_REAL.txt not found!\n");
}

$lines = explode("\n", trim($rawData));
$updated = 0;
$notFound = 0;
$alreadyFilled = 0;

echo "🚀 Starting update of " . count($lines) . " devices...\n\n";

foreach ($lines as $index => $line) {
    $parts = preg_split('/\t+/', trim($line));
    
    if (count($parts) >= 4) {
        $deviceName = trim($parts[0]);
        $unitCode = trim($parts[1]) === '-' ? null : trim($parts[1]);
        $location = trim($parts[2]);
        $series = trim($parts[3]);
        
        // Cek dulu apakah device sudah ada dan datanya kosong
        $checkStmt = $pdo->prepare("
            SELECT id, unit_code, location, series 
            FROM devices 
            WHERE device_name = :device_name
        ");
        $checkStmt->execute([':device_name' => $deviceName]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existing) {
            $notFound++;
            echo "X";
        } else {
            // Cek apakah data masih kosong
            $isEmpty = (empty($existing['unit_code']) || is_null($existing['unit_code'])) 
                    && (empty($existing['location']) || is_null($existing['location']))
                    && (empty($existing['series']) || is_null($existing['series']));
            
            if ($isEmpty) {
                // UPDATE karena data kosong
                $stmt = $pdo->prepare("
                    UPDATE devices 
                    SET unit_code = :unit_code,
                        location = :location,
                        series = :series,
                        updated_at = NOW()
                    WHERE device_name = :device_name
                ");
                
                $stmt->execute([
                    ':unit_code' => $unitCode,
                    ':location' => $location,
                    ':series' => $series,
                    ':device_name' => $deviceName
                ]);
                
                $updated++;
                echo ".";
            } else {
                // Sudah ada data, skip
                $alreadyFilled++;
                echo "-";
            }
        }
        
        // New line every 50
        if (($index + 1) % 50 === 0) {
            echo " [" . ($index + 1) . "]\n";
        }
    }
}

echo "\n\n";
echo "========================================\n";
echo "✅ UPDATE COMPLETED!\n";
echo "========================================\n";
echo "📊 Total: " . count($lines) . " devices\n";
echo "✅ Updated: {$updated} devices\n";
echo "- Already Filled: {$alreadyFilled} devices\n";
echo "⚠️  Not Found: {$notFound} devices\n";
echo "========================================\n";
echo "\nKeterangan:\n";
echo ". = Device berhasil diupdate\n";
echo "- = Device sudah ada data (skip)\n";
echo "X = Device tidak ditemukan di database\n";
