<?php
$lines = file('storage/logs/laravel.log');
$errors = array_filter($lines, function($line) {
    return strpos($line, 'production.ERROR') !== false || strpos($line, 'local.ERROR') !== false;
});
echo trim(end($errors)) . "\n";
