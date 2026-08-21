<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=vss', 'root', '');
    echo "✅ Database connection: OK\n";
    echo "Database: vss\n";
} catch(Exception $e) {
    echo "❌ Database connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
}
?>
