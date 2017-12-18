<?php

ini_set('date.timezone', 'Asia/Tokyo');
try {
    //require '../db.php';
    $systemStartTime_yw84scpcDAekiiS = microtime(true);
    require_once '../app/web/globalParams.php';
    require_once '../monogusa/MonogusaUtil.php';
    require_once '../monogusa/MonogusaView.php';
    require_once '../monogusa/MonogusaWeb.php';
    require_once '../monogusa/MonogusaDB.php';
    webInIt();
    require_once '../app/web/common.php';
    $requireList = getFileLists('../app/web');
    foreach ($requireList as $require) {
        if ($require == '../app/web/common.php') {
            continue;
        }
        if ($require == '../app/web/globalParams.php') {
            continue;
        }
        require_once $require;
    }
    before();
    response(); // after()はresponseの最後で実行される
} catch (Exception $e) {
    Log::error($e->getMessage().'  '.$e->getFile().'('.$e->getLine().')');
    http_response_code(500);
    ob_end_clean();
    if(defined("API_FLG") && API_FLG){
        if ($e->getMessage() == 'token miss match') {
            responseJson(['message'=> 'token miss match'], 500);
        } else {
            responseJson(['message'=> 'エラーが発生しました。'], 500);
        }
    }else{
        echo $e->getMessage();
    } 
} finally {
    $mesureFlg = false;
    $mesureText = '';
    if (setting('mesure_time')) {
        $mesureFlg = true;
        $systemEndTime_yw84scpcDAekiiS = microtime(true);
        $mesureText = 'process time : ' . ($systemEndTime_yw84scpcDAekiiS - $systemStartTime_yw84scpcDAekiiS);
    }
    if (setting('mesure_memory')) {
        $mesureFlg = true;
        $peakmem = number_format(memory_get_peak_usage());
        if ($mesureText != '') {
            $mesureText .= ',  ';
        }
        $mesureText .= 'memory : ' . $peakmem;
    }
    if ($mesureFlg) {
        Log::info($mesureText);
    }
}

