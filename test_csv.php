<?php
$match = "(1, '75482223', 'GPE-B-806', '-', 'Area Operasional', 'DOZER', NULL, NULL, NULL, NULL, 'active', NULL, '2026-06-10 23:19:09', '2026-07-10 16:52:16', NULL)";
$match = substr($match, 1, -1); 
$parts = str_getcsv($match, ',', "'"); 
var_dump($parts); 
echo "deviceId: " . trim($parts[1], " '") . "\nlocation: " . trim($parts[4], " '") . "\n";
