<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\SpeedController;

$request = Request::create('/speed/data', 'GET', [
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d'),
    'speed_filter' => 'low',
    'draw' => 1,
    'start' => 0,
    'length' => 50
]);

$controller = new SpeedController();
$response = $controller->getData($request);
echo $response->getContent();
