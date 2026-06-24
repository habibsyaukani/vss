<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                   CEK USER ADMIN CREDENTIALS                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Get all users
$users = DB::table('users')->get();

echo "📊 Total Users: " . count($users) . "\n\n";

foreach ($users as $user) {
    echo "─────────────────────────────────────────────────────────────────\n";
    echo "ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Role: {$user->role}\n";
    echo "Status: " . ($user->status ?? 'N/A') . "\n";
    echo "Created: {$user->created_at}\n";
    
    if ($user->role === 'admin') {
        echo "🔑 ADMIN ACCOUNT - Use this for admin login\n";
    }
    echo "\n";
}

echo "─────────────────────────────────────────────────────────────────\n";
echo "\n";
echo "📝 CATATAN:\n";
echo "   Password default biasanya: password, password123, atau admin123\n";
echo "   Coba login dengan email di atas\n";
echo "\n";
