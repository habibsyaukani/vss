<?php
/**
 * Script untuk generate array devices dari data Excel
 * Jalankan: php generate_devices_array.php > devices_array.txt
 */

$excelData = <<<'EOD'
GPE-B-806|-|Area Operasional|DOZER
GPE-B-807|-|Area Operasional|DOZER
GPE-B-808|-|Area Operasional|DOZER
GPE-B-809|-|Area Operasional|DOZER
GPE-B-811|-|Area Operasional|DOZER
GPE-B-812|-|Area Operasional|DOZER
GPE-B-813|-|Area Operasional|DOZER
GPE-B-815|-|Area Operasional|DOZER
GPE-B-816|-|Area Operasional|DOZER
GPE-B-818|-|Area Operasional|DOZER
GPE-B-819|-|Area Operasional|DOZER
GPE-B-820|-|Area Operasional|DOZER
GPE-B-821|-|Area Operasional|DOZER
GPE-B-822|-|Area Operasional|DOZER
GPE-B-825|-|Area Operasional|DOZER
GPE-B-826|-|Area Operasional|DOZER
GPE-B-827|-|Area Operasional|DOZER
GPE-B-828|-|Area Operasional|DOZER
GPE-B-829|-|Area Operasional|DOZER
GPE-B-830|-|Area Operasional|DOZER
GPE-B-831|-|Area Operasional|DOZER
GPE-B-832|-|Area Operasional|DOZER
GPE-B-833|-|Area Operasional|DOZER
GPE-B-835|-|Area Operasional|DOZER
GPE-B-836|-|Area Operasional|DOZER
GPE-B-837|-|Area Operasional|DOZER
GPE-B-838|-|Area Operasional|DOZER
GPE-B-839|-|Area Operasional|DOZER
GPE-B-856|-|Area Operasional|DOZER
GPE-B-857|-|Area Operasional|DOZER
GPE-B-860|-|Area Operasional|DOZER
GPE-B-866|-|Area Operasional|DOZER
GPE-B-867|-|Area Operasional|DOZER
GPE-B-871|-|Area Operasional|DOZER
GPE-B-873|-|Area Operasional|DOZER
GPE-B-876|-|Area Operasional|DOZER
GPE-B-877|-|Area Operasional|DOZER
GPE-B-878|-|Area Operasional|DOZER
GPE-B-879|-|Area Operasional|DOZER
GPE-B-880|-|Area Operasional|DOZER
GPE-B-881|-|Area Operasional|DOZER
GPE-B-882|-|Area Operasional|DOZER
GPE-B-883|-|Area Operasional|DOZER
GPE-B-885|-|Area Operasional|DOZER
GPE-B-886|-|Area Operasional|DOZER
GPE-B-887|-|Area Operasional|DOZER
GPE-DT-1000|GPE1000|M.SERVICE|DT BARU FMX 400
GPE-DT-1001|GPE1001|M.SERVICE|DT BARU FMX 400
GPE-DT-1002|GPE1002|M.SERVICE|DT BARU FMX 400
GPE-DT-1003|GPE1003|M.SERVICE|DT BARU FMX 400
EOD;

$lines = explode("\n", trim($excelData));

echo "            // DEVICES DATA (" . count($lines) . " records)\n";

foreach ($lines as $line) {
    $parts = explode('|', trim($line));
    if (count($parts) === 4) {
        $deviceName = $parts[0];
        $unitCode = $parts[1];
        $location = $parts[2];
        $series = $parts[3];
        
        echo "            ['device_name' => '{$deviceName}', 'unit_code' => '{$unitCode}', 'location' => '{$location}', 'series' => '{$series}'],\n";
    }
}
