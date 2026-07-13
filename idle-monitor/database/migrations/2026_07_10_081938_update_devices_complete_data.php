<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Data devices lengkap dari user
        $devices = [
            // BULLDOZER (GPE-B-xxx) - 46 units
            ['device_name' => 'GPE-B-806', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-807', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-808', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-809', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-811', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-812', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-813', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-815', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-816', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-818', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-819', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-820', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-821', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-822', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-825', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-826', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-827', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-828', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-829', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-830', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-831', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-832', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-833', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-835', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-836', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-837', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-838', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-839', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-856', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-857', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-860', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-866', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-867', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-871', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-873', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-876', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-877', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-878', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-879', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-880', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-881', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-882', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-883', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-885', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-886', 'unit_code' => null, 'location' => null, 'series' => null],
            ['device_name' => 'GPE-B-887', 'unit_code' => null, 'location' => null, 'series' => null],
            
            // DUMP TRUCK BARU (GPE-DT-1xxx) - 173 units
            ['device_name' => 'GPE-DT-1000', 'unit_code' => 'GPE1000', 'location' => 'M.SERVICE', 'series' => 'DT BARU FMX 400'],
            ['device_name' => 'GPE-DT-1001', 'unit_code' => 'GPE1001', 'location' => 'M.SERVICE', 'series' => 'DT BARU FMX 400'],
            ['device_name' => 'GPE-DT-1002', 'unit_code' => 'GPE1002', 'location' => 'M.SERVICE', 'series' => 'DT BARU FMX 400'],
            ['device_name' => 'GPE-DT-1003', 'unit_code' => 'GPE1003', 'location' => 'M.SERVICE', 'series' => 'DT BARU FMX 400'],
            ['device_name' => 'GPE-DT-1005', 'unit_code' => 'GPE1005', 'location' => 'M.SERVICE', 'series' => 'DT BARU FMX 400'],
            ['device_name' => 'GPE-DT-1006', 'unit_code' => 'GPE1006', 'location' => 'M.SERVICE', 'series' => 'DT BARU FMX 400'],
            ['device_name' => 'GPE-DT-1007', 'unit_code' => 'GPE1007', 'location' => 'M.SERVICE', 'series' => 'DT BARU FMX 400'],
            ['device_name' => 'GPE-DT-1008', 'unit_code' => 'GPE1008', 'location' => 'M.SERVICE', 'series' => 'DT BARU FMX 400'],
            ['device_name' => 'GPE-DT-1009', 'unit_code' => 'GPE1009', 'location' => 'MUD UTARA', 'series' => 'DT BARU FMX 400'],
            ['device_name' => 'GPE-DT-1010', 'unit_code' => 'GPE1010', 'location' => 'MUD UTARA', 'series' => 'DT BARU FMX 400'],
