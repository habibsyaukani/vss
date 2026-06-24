<?php
/**
 * Fix Login - Create Test Users with Password Verification
 * Direct execution to bypass artisan issues
 */

// Bootstrap Laravel
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    echo "=================================================================\n";
    echo "FIXING LOGIN - CREATE TEST USERS\n";
    echo "=================================================================\n\n";
    
    // Check existing users
    echo "Checking existing users...\n";
    $existingUsers = User::all();
    echo "Found " . $existingUsers->count() . " users\n";
    foreach ($existingUsers as $u) {
        echo "  - " . $u->email . "\n";
    }
    
    // Clear existing users
    echo "\nClearing all users...\n";
    User::query()->delete();
    
    // Create Admin
    echo "\nCreating admin user...\n";
    $admin = User::create([
        'name' => 'Administrator',
        'email' => 'admin@vss.com',
        'password' => Hash::make('admin123'),
        'role' => 'admin',
        'status' => 'active',
    ]);
    echo "✓ Admin created: admin@vss.com\n";
    
    // Create Fleet Manager
    echo "\nCreating fleet manager user...\n";
    $manager = User::create([
        'name' => 'Fleet Manager',
        'email' => 'manager@vss.com',
        'password' => Hash::make('manager123'),
        'role' => 'fleet_manager',
        'status' => 'active',
    ]);
    echo "✓ Fleet Manager created: manager@vss.com\n";
    
    // Verify passwords
    echo "\n=================================================================\n";
    echo "VERIFYING PASSWORDS\n";
    echo "=================================================================\n\n";
    
    $testAdmin = User::where('email', 'admin@vss.com')->first();
    if ($testAdmin && Hash::check('admin123', $testAdmin->password)) {
        echo "✓ Admin password verification: SUCCESS\n";
    } else {
        echo "✗ Admin password verification: FAILED\n";
    }
    
    $testManager = User::where('email', 'manager@vss.com')->first();
    if ($testManager && Hash::check('manager123', $testManager->password)) {
        echo "✓ Manager password verification: SUCCESS\n";
    } else {
        echo "✗ Manager password verification: FAILED\n";
    }
    
    // Show final users
    echo "\n=================================================================\n";
    echo "FINAL USER LIST\n";
    echo "=================================================================\n\n";
    
    $users = User::all();
    echo "Total users: " . $users->count() . "\n\n";
    foreach ($users as $user) {
        echo "  Email: " . $user->email . "\n";
        echo "  Role:  " . $user->role . "\n";
        echo "  Status: " . $user->status . "\n\n";
    }
    
    echo "=================================================================\n";
    echo "✓ SUCCESS! Users are ready for login\n";
    echo "=================================================================\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. Refresh browser (Ctrl+F5)\n";
    echo "2. Clear cache (Ctrl+Shift+Delete)\n";
    echo "3. Go to: http://localhost:8000/login\n";
    echo "4. Login with:\n";
    echo "   Email: manager@vss.com\n";
    echo "   Password: manager123\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
