<?php

$baseMemoryUsage = memory_get_usage();

// setting
ini_set('date.timezone', 'Asia/Tokyo');
//ini_set('memory_limit', '512M');
mb_internal_encoding("UTF-8");

require_once '.setting.php';
require 'vendor/autoload.php';
require_once './app/Monogusa/class.php';
require_once './app/Monogusa/main.php';

$peak = memory_get_peak_usage();
$maxMemoryUsage = ($peak - $baseMemoryUsage) / 1024;
$memoryUnit = 'KB';
if ($maxMemoryUsage >= 1024) {
    $maxMemoryUsage = $maxMemoryUsage / 1024;
    $memoryUnit = 'MB';
}
$maxMemoryUsage = round($maxMemoryUsage, 2);
Log::info("Max Memory Usage : $maxMemoryUsage $memoryUnit");
