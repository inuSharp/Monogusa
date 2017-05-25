<?php

ini_set('date.timezone', 'Asia/Tokyo');
try {
    $systemStartTime_yw84scpcDAekiiS = microtime(true);
    require_once 'vendor/Monogusa.php';
    require_once 'Code/common.php';
    $requireList = getFileLists('Code');
    foreach ($requireList as $require) {
        if ($require == 'Code/common.php') {
            continue;
        }
        require_once $require;
    }
    webInIt();
    response();
} catch (Exception $e) {
    Log::error($e->getMessage().'  '.$e->getFile().'('.$e->getLine().')');
    http_response_code(500);
    if(defined("API_FLG") && API_FLG){
        echo json_encode(['message'=>'inernal server error!']);
    }else{
        echo $e->getMessage();
    } 
} finally {
    if (setting('mesure_time')) {
        $systemEndTime_yw84scpcDAekiiS = microtime(true);
        Log::info('process time : ' . ($systemEndTime_yw84scpcDAekiiS - $systemStartTime_yw84scpcDAekiiS));
    }
    if (setting('mesure_memory')) {
        $peakmem = number_format(memory_get_peak_usage());
        Log::info('memory : ' . $peakmem);
    }
}

