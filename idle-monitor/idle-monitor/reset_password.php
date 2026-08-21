<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('role', 'admin')->first();
if (!$user) {
    echo "Admin user not found. Creating one...\n";
    $user = new \App\Models\User();
    $user->name = 'Administrator';
    $user->username = 'admin';
    $user->role = 'admin';
    $user->status = 'active';
}

$user->password = \Illuminate\Support\Facades\Hash::make('admin123');
$user->save();

echo "Success! Admin username: '" . $user->username . "', Password: 'admin123'\n";
