<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                  TEST ADMIN LOGIN CREDENTIALS                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Test admin credentials
$credentials = [
    ['email' => 'admin@vss.com', 'password' => 'admin123'],
    ['email' => 'admin@vss.com', 'password' => 'password123'],
    ['email' => 'admin@vss.com', 'password' => 'password'],
    ['email' => 'manager@vss.com', 'password' => 'manager123'],
];

foreach ($credentials as $cred) {
    echo "Testing: {$cred['email']} / {$cred['password']}\n";
    
    $user = User::where('email', $cred['email'])->first();
    
    if (!$user) {
        echo "   ❌ User not found\n\n";
        continue;
    }
    
    if (Hash::check($cred['password'], $user->password)) {
        echo "   ✅ VALID! This is the correct password!\n";
        echo "   Role: {$user->role}\n";
        echo "   Name: {$user->name}\n\n";
    } else {
        echo "   ❌ Invalid password\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
