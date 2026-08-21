<?php
/**
 * Create Admin and Manager Fleet accounts
 * Run this to restore users after migration
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "===========================================\n";
echo "  CREATE ADMIN & MANAGER FLEET ACCOUNTS\n";
echo "===========================================\n\n";

try {
    DB::beginTransaction();
    
    // Check existing users
    echo "[1] Checking existing users...\n";
    $existingAdmin = DB::table('users')->where('email', 'admin@vss.com')->first();
    $existingManager = DB::table('users')->where('email', 'manager@vss.com')->first();
    
    $created = 0;
    $skipped = 0;
    
    // Create Admin account
    if ($existingAdmin) {
        echo "    ⚠️  Admin already exists (admin@vss.com)\n";
        $skipped++;
    } else {
        echo "    Creating Admin account...\n";
        DB::table('users')->insert([
            'name' => 'Administrator',
            'email' => 'admin@vss.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "    ✓ Admin created\n";
        $created++;
    }
    
    // Create Manager Fleet account
    if ($existingManager) {
        echo "    ⚠️  Manager Fleet already exists (manager@vss.com)\n";
        $skipped++;
    } else {
        echo "    Creating Manager Fleet account...\n";
        DB::table('users')->insert([
            'name' => 'Manager Fleet',
            'email' => 'manager@vss.com',
            'password' => Hash::make('manager123'),
            'role' => 'fleet_manager',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "    ✓ Manager Fleet created\n";
        $created++;
    }
    
    DB::commit();
    
    echo "\n===========================================\n";
    echo "  SUMMARY\n";
    echo "===========================================\n";
    echo "✅ Created: $created accounts\n";
    if ($skipped > 0) {
        echo "⚠️  Skipped: $skipped accounts (already exist)\n";
    }
    echo "\n";
    
    // Display accounts
    echo "===========================================\n";
    echo "  ACCOUNT CREDENTIALS\n";
    echo "===========================================\n\n";
    
    echo "1️⃣  ADMIN ACCOUNT:\n";
    echo "   Email    : admin@vss.com\n";
    echo "   Password : admin123\n";
    echo "   Role     : admin\n\n";
    
    echo "2️⃣  MANAGER FLEET ACCOUNT:\n";
    echo "   Email    : manager@vss.com\n";
    echo "   Password : manager123\n";
    echo "   Role     : fleet_manager\n\n";
    
    echo "===========================================\n";
    echo "  ✅ COMPLETED!\n";
    echo "===========================================\n\n";
    
    echo "⚠️  IMPORTANT:\n";
    echo "   Please change passwords after first login!\n\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    exit(1);
}
?>
