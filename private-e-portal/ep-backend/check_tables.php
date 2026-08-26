<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'");
foreach ($tables as $t) {
    $name = strtolower($t->TABLE_NAME);
    if (strpos($name, 'wfh') !== false || strpos($name, 'leave') !== false || strpos($name, 'ob') !== false || strpos($name, 'work') !== false || strpos($name, 'sched') !== false || strpos($name, 'rest') !== false) {
        echo "Table: " . $t->TABLE_NAME . "\n";
    }
}
