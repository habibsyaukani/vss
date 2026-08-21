<?php
$lines = file('g:\project\vss\idle-monitor\DATA_397_DEVICES.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$updated = 0;
foreach ($lines as $line) {
    $parts = explode("\t", $line);
    if (count($parts) >= 4) {
        $deviceName = trim($parts[1]);
        $location = trim($parts[3]);
        if ($location !== 'NULL' && $location !== '') {
            $updated++;
        }
    }
}
echo "Total: $updated\n";
