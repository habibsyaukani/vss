<?php
/**
 * =====================================================
 * AUTO-GENERATE & RUN SQL UPDATE - 397 DEVICES
 * =====================================================
 * 
 * CARA PAKAI:
 * 1. Jalankan: php GENERATE_SQL_AND_RUN.php
 * 2. DONE! 397 devices terupdate otomatis!
 * 
 * File ini akan:
 * - Generate SQL UPDATE statements
 * - Langsung execute ke database
 * - Tampilkan hasil
 * =====================================================
 */

// Database connection (dari .env Laravel)
$host = '127.0.0.1';
$dbname = 'vss';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database: {$dbname}\n\n";
    
    // Data lengkap 397 devices (dari pesan user)
    $rawData = file_get_contents(__DIR__ . '/DATA_397_DEVICES.txt');
    
    if (!$rawData) {
        die("❌ File DATA_397_DEVICES.txt not found!\n");
    }
    
    $lines = explode("\n", trim($rawData));
    $updated = 0;
    $notFound = 0;
    
    echo "🚀 Starting update of " . count($lines) . " devices...\n\n";
    
    foreach ($lines as $index => $line) {
        // Format: id  device_name  unit_code  location  series
        $parts = preg_split('/\t+/', trim($line));
        
        if (count($parts) >= 5) {
            $deviceName = trim($parts[1]);
            $unitCode = trim($parts[2]) === '-' ? null : trim($parts[2]);
            $location = trim($parts[3]);
            $series = trim($parts[4]);
            
            // Execute UPDATE
            $stmt = $pdo->prepare("
                UPDATE devices 
                SET unit_code = :unit_code,
                    location = :location,
                    series = :series,
                    updated_at = NOW()
                WHERE device_name = :device_name
            ");
            
            $result = $stmt->execute([
                ':unit_code' => $unitCode,
                ':location' => $location,
                ':series' => $series,
                ':device_name' => $deviceName
            ]);
            
            if ($stmt->rowCount() > 0) {
                $updated++;
                echo ".";
            } else {
                $notFound++;
                echo "X";
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
    echo "⚠️  Not Found: {$notFound} devices\n";
    echo "========================================\n";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
