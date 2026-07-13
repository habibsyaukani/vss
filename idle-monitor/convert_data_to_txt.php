<?php
/**
 * Script untuk convert data dari format table ke pipe-delimited
 * Jalankan: php convert_data_to_txt.php
 * Output: ALL_397_DEVICES_DATA.txt (siap dipakai oleh command)
 */

// Data lengkap 397 devices dari user (paste data lengkap Anda di sini)
$rawData = <<<'DATA'
1	GPE-B-806	-	Area Operasional	DOZER
2	GPE-B-807	-	Area Operasional	DOZER
3	GPE-B-808	-	Area Operasional	DOZER
4	GPE-B-809	-	Area Operasional	DOZER
5	GPE-B-811	-	Area Operasional	DOZER
6	GPE-B-812	-	Area Operasional	DOZER
7	GPE-B-813	-	Area Operasional	DOZER
8	GPE-B-815	-	Area Operasional	DOZER
9	GPE-B-816	-	Area Operasional	DOZER
10	GPE-B-818	-	Area Operasional	DOZER
11	GPE-B-819	-	Area Operasional	DOZER
12	GPE-B-820	-	Area Operasional	DOZER
13	GPE-B-821	-	Area Operasional	DOZER
14	GPE-B-822	-	Area Operasional	DOZER
15	GPE-B-825	-	Area Operasional	DOZER
16	GPE-B-826	-	Area Operasional	DOZER
17	GPE-B-827	-	Area Operasional	DOZER
18	GPE-B-828	-	Area Operasional	DOZER
19	GPE-B-829	-	Area Operasional	DOZER
20	GPE-B-830	-	Area Operasional	DOZER
21	GPE-B-831	-	Area Operasional	DOZER
22	GPE-B-832	-	Area Operasional	DOZER
23	GPE-B-833	-	Area Operasional	DOZER
24	GPE-B-835	-	Area Operasional	DOZER
25	GPE-B-836	-	Area Operasional	DOZER
26	GPE-B-837	-	Area Operasional	DOZER
27	GPE-B-838	-	Area Operasional	DOZER
28	GPE-B-839	-	Area Operasional	DOZER
29	GPE-B-856	-	Area Operasional	DOZER
30	GPE-B-857	-	Area Operasional	DOZER
31	GPE-B-860	-	Area Operasional	DOZER
32	GPE-B-866	-	Area Operasional	DOZER
33	GPE-B-867	-	Area Operasional	DOZER
34	GPE-B-871	-	Area Operasional	DOZER
35	GPE-B-873	-	Area Operasional	DOZER
36	GPE-B-876	-	Area Operasional	DOZER
37	GPE-B-877	-	Area Operasional	DOZER
38	GPE-B-878	-	Area Operasional	DOZER
39	GPE-B-879	-	Area Operasional	DOZER
40	GPE-B-880	-	Area Operasional	DOZER
41	GPE-B-881	-	Area Operasional	DOZER
42	GPE-B-882	-	Area Operasional	DOZER
43	GPE-B-883	-	Area Operasional	DOZER
44	GPE-B-885	-	Area Operasional	DOZER
45	GPE-B-886	-	Area Operasional	DOZER
46	GPE-B-887	-	Area Operasional	DOZER
47	GPE-DT-1000	GPE1000	M.SERVICE	DT BARU FMX 400
48	GPE-DT-1001	GPE1001	M.SERVICE	DT BARU FMX 400
49	GPE-DT-1002	GPE1002	M.SERVICE	DT BARU FMX 400
50	GPE-DT-1003	GPE1003	M.SERVICE	DT BARU FMX 400
DATA;

$lines = explode("\n", trim($rawData));
$output = [];

foreach ($lines as $line) {
    // Split by tab
    $parts = preg_split('/\t+/', trim($line));
    
    if (count($parts) >= 5) {
        // Format: id  device_name  unit_code  location  series
        $deviceName = trim($parts[1]);
        $unitCode = trim($parts[2]);
        $location = trim($parts[3]);
        $series = trim($parts[4]);
        
        // Convert to pipe format
        $output[] = "{$deviceName}|{$unitCode}|{$location}|{$series}";
    }
}

// Save to file
$outputFile = __DIR__ . '/storage/app/ALL_397_DEVICES_DATA.txt';
file_put_contents($outputFile, implode("\n", $output));

echo "✅ Converted " . count($output) . " devices\n";
echo "📁 Saved to: {$outputFile}\n";
echo "\nNow run: php artisan devices:update-all\n";
