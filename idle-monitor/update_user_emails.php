<?php
/**
 * Update user email addresses to @vss.com domain
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "===========================================\n";
echo "  UPDATE USER EMAIL ADDRESSES\n";
echo "===========================================\n\n";

try {
    DB::beginTransaction();
    
    // Update Admin email
    echo "[1] Updating Admin email...\n";
    $adminUpdated = DB::table('users')
        ->where('email', 'admin@gpe.com')
        ->update(['email' => 'admin@vss.com', 'updated_at' => now()]);
    
    if ($adminUpdated) {
        echo "    ✓ Admin email updated: admin@gpe.com → admin@vss.com\n";
    } else {
        echo "    ⚠️  Admin with admin@gpe.com not found\n";
    }
    
    // Update Manager email
    echo "\n[2] Updating Manager Fleet email...\n";
    $managerUpdated = DB::table('users')
        ->where('email', 'manager@gpe.com')
        ->update(['email' => 'manager@vss.com', 'updated_at' => now()]);
    
    if ($managerUpdated) {
        echo "    ✓ Manager email updated: manager@gpe.com → manager@vss.com\n";
    } else {
        echo "    ⚠️  Manager with manager@gpe.com not found\n";
    }
    
    DB::commit();
    
    echo "\n===========================================\n";
    echo "  VERIFICATION\n";
    echo "===========================================\n";
    
    $users = DB::table('users')
        ->whereIn('email', ['admin@vss.com', 'manager@vss.com'])
        ->get(['name', 'email', 'role']);
    
    foreach ($users as $user) {
        echo "✅ {$user->name} - {$user->email} ({$user->role})\n";
    }
    
    echo "\n===========================================\n";
    echo "  UPDATED CREDENTIALS\n";
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
    echo "===========================================\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    exit(1);
}
?>
