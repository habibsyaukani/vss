<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$devices = \App\Models\Device::all();
$sql = "TRUNCATE TABLE devices;\n";
foreach ($devices as $d) {
    $sql .= "INSERT INTO devices (id, device_id, device_name, plate_no, sim_number, group_name, location, status, created_at, updated_at, imei, series) VALUES (";
    $sql .= "'" . addslashes($d->id) . "', ";
    $sql .= "'" . addslashes($d->device_id) . "', ";
    $sql .= "'" . addslashes($d->device_name) . "', ";
    $sql .= "'" . addslashes($d->plate_no) . "', ";
    $sql .= "'" . addslashes($d->sim_number) . "', ";
    $sql .= "'" . addslashes($d->group_name) . "', ";
    $sql .= "'" . addslashes($d->location) . "', ";
    $sql .= "'" . addslashes($d->status) . "', ";
    $sql .= "'" . addslashes($d->created_at) . "', ";
    $sql .= "'" . addslashes($d->updated_at) . "', ";
    $sql .= "'" . addslashes($d->imei) . "', ";
    $sql .= "'" . addslashes($d->series) . "');\n";
}
file_put_contents('devices_dump.sql', $sql);
echo "Exported " . count($devices) . " devices.";
