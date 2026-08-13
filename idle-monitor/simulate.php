<?php
require __DIR__.'/vendor/autoload.php';
putenv('APP_DEBUG=true');
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::find(2); // admin
auth()->login($user);

try {
    $request = Illuminate\Http\Request::create('/idle-alarm', 'GET');
    $response = app()->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() == 500) {
        echo "Error: " . strip_tags($response->getContent());
    }
} catch (\Exception $e) {
    echo "Caught Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString();
} catch (\Throwable $e) {
    echo "Caught Throwable: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
