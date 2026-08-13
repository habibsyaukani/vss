<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('username', 'admin')->first();
if ($user) {
    $user->password = \Illuminate\Support\Facades\Hash::make('admin123');
    $user->save();
    echo "Password changed successfully.";
} else {
    echo "User admin not found.";
}
