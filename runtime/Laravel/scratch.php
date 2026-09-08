<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$val = 0;
$fmt = function($val) {
    if ($val === '' || $val === null || $val === '-') return '';
    if (is_numeric($val)) return number_format((float)$val, 2, '.', '');
    return $val;
};
echo $fmt($val) . "\n";
