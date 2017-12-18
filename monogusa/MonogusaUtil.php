<?php
ini_set( 'display_errors' , 0 );

/*********************************************************
 * Framework Common
 */
error_reporting(E_ALL);
set_error_handler( 'my_error_handler', E_ALL );
register_shutdown_function('my_shutdown_handler');

function my_error_handler ( $errno, $errstr, $errfile, $errline, $errcontext ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function my_shutdown_handler(){
    $isError = false;
    if ($error = error_get_last()){
         switch($error['type']){
         case E_ERROR:
         case E_PARSE:
         case E_CORE_ERROR:
         case E_CORE_WARNING:
         case E_COMPILE_ERROR:
         case E_COMPILE_WARNING:
              $isError = true;
                     break;
             }
        }
    if ($isError){
          $text = $error['type'] . '  ' . $error['message'] . '  ' .  
              $error['file'] . '  ' .  
              $error['line'];
          if (class_exists('Log')) {
              Log::error($text);
          } else {
              file_put_contents('exception.log',$text);
          }
    }
}
function setting($key,$default=null) {
    static $setting;
    if (is_null($setting)) {
        //$mode = 'develop';
        //if (file_exists('.production')) {
        //    $mode = 'production';
        //}
        //$setting = parse_ini_file(".setting.".$mode, true);
        $settingPath = rtrim(__DIR__, '/');
        $settingPath = str_replace(basename($settingPath), '', $settingPath);
        $settingPath = rtrim($settingPath, '/');
        $setting = require_once($settingPath . '/.setting.php');
    }
    if (array_key_exists($key, $setting)) {
        return $setting[$key];
    } else {
        return $default;
    }
}
function C($key, $default=null) {
    static $consts;
    if (is_null($consts)) {
        $filepath = rtrim(__DIR__, '/');
        $filepath = str_replace(basename($filepath), '', $filepath);
        $filepath = rtrim($filepath, '/');
        $consts   = require_once($filepath . '/const.php');
    }
    if (array_key_exists($key, $consts)) {
        return $consts[$key];
    } else {
        return $default;
    }
}
function makeRandStr($length = 30)
{
    //使用する文字
    $char = '1234567890abcdefghijklmnopqrstuvwxyz';
    $charlen = mb_strlen($char);
    $result = "";
    for($i=1;$i<=$length;$i++){
      $index = mt_rand(0, $charlen - 1);
      $result .= mb_substr($char, $index, 1);
    }
    return $result;
}
function makeUniqueFileName($targetDir)
{
    $tmp = '';
    $date = date("YmdHis");
    $cnt = 0;
    do{
        $cnt++;
        $tmp = $date . '_' .$cnt;
    } while(file_exists($targetDir . '/' . $tmp));
    return $tmp;
}
function xml($path)
{
    $xml = simplexml_load_file($path);
    return get_object_vars($xml);
}
function bind($data, $text) {
    $bef = [];
    $aft = [];
    foreach ($data as $key => $value) {
        $value = str_replace(["'", "\\"], ["\'", "\\\\"], $value);
        $bef[] = ':'.$key;
        $aft[] = $value;
    }
    return str_replace($bef, $aft, $text);
}
function arrayToCsv($data)
{
    $csv = '';
    foreach ($data as $row) {
        $csvrow = '';
        foreach ($row as $col) {
            if ($csvrow != '') {
                $csvrow .= ',';
            }
            $csvrow .= str_replace("\n", "@LF@", $col);
        }
        if ($csv != '') {
            $csv .= "\n";
        }
        $csv .= $csvrow;
    }
    return $csv;
}
function csvToArray($csv,$existsHeader = true)
{
    $lines = explode("\n",preg_replace("/^(\s)*(\r|\n|\r\n)/m", "", $csv));

    $start = 0;
    if ($existsHeader) {
        $header = explode(',', $lines[0]);
        $start = 1;
    }

    $index = -1;
    $ret   = [];
    for ($i=$start;$i<count($lines);$i++) {
        if ($lines[$i] == '') {
            continue;
        }
        $index++;
        $line = explode(',', $lines[$i]);
        if ($existsHeader) {
            $ret[$index] = [];
            $colIndex = -1;
            foreach ($header as $name) {
                $colIndex++;
                $ret[$index][$name] = $line[$colIndex];
            }
        } else {
            $ret[$index] = $line;
        }
        
    }
    return $ret;
}
function snakeToPascal($snake)
{
    $strs = explode('_', $snake);
    $ret  = '';
    foreach ($strs as $str) {
        $ret .= ucfirst($str);
    }
    return $ret;
}
function checkWindows()
{
    if (DIRECTORY_SEPARATOR == '\\') {
        return true;
    } else {
        return false;
    }
}

function getFileLists($folder, $order = 0, &$maxCnt = -1, &$startCnt = 0, &$cnt = 0) {
    $files = scandir($folder, $order); // 1:降順
    $lists = [];
    foreach($files as $file) {
        if ($file == ".." || $file == "." || $file == ".svn") {continue;}

        if (is_dir($folder.'/'.$file)) {
            $lists = array_merge($lists, getFileLists($folder.'/'.$file, $order, $maxCnt, $startCnt, $cnt));
        } else {
            $cnt++;
            if ($startCnt <= $cnt) {
                $lists[] = $folder.'/'.$file;
            }
        }
        if ($maxCnt != -1 && $maxCnt == $cnt) {
            break;
        }
    }
    return $lists;
}

// えーびーしーいーえふあいじぇいけいえるえむえぬおーぴーあーるえすゆーえっくすわい
// abcefijklmnoprsuxy
function humanCheck($checkValue = null) {
    $data = csvToArray(file_get_contents(dataDir().'/human_check.txt'), false);
    if (!is_null($checkValue)) {
        if ($data[getSession('humanCheckNo')][1] == $checkValue) {
            return true;
        } else {
            return false;
        }
    }

    $checkNo = mt_rand(0,count($data)-1);
    $checkRow = $data[$checkNo];
    $caption = $checkRow[0];
    setSession('humanCheckNo', $checkNo);
    return $caption;
}
function lineNotify($message)
{
    $url = "https://notify-api.line.me/api/notify";
    $token = '';

    $data = ["message" => $message];
    $data = http_build_query($data, "", "&");

    $options = array(
        'http'=>array(
            'method'=>'POST',
            'header'=>"Authorization: Bearer " . $token . "\r\n"
                      . "Content-Type: application/x-www-form-urlencoded\r\n"
                      . "Content-Length: ".strlen($data)  . "\r\n" ,
            'content' => $data
        )
    );
    $context = stream_context_create($options);
    $resultJson = file_get_contents($url, FALSE, $context );
    $resutlArray = json_decode($resultJson,TRUE);
    if( $resutlArray['status'] != 200)  {
        \Log::error("lineに通知失敗しました。");
        return false;
    }
    return true;
}
function sendMail($to, $title, $body, $from)
{
    //メールの内容
    $from = "From: " . $from;
    //$from = "From: my-mail@example.com\r\nReturn-Path: my-mail@example.com";

    //メールの送信
    $send_mail = mb_send_mail($to, $title, $body, $from);

    //メールの送信に問題ないかチェック
    if (!$send_mail) {
        throw new \Exception("send mail exception");
    }
}

//echo "1日前"   . date("Y/m/d", strtotime("-1 day"  ));
//echo "1ヶ月前" . date("Y/m/d", strtotime("-1 month"));
//echo "1年前"   . date("Y/m/d", strtotime("-1 year" ));
//echo "1週間前" . date("Y/m/d", strtotime("-1 week" ));
// 指定日付から○日の取得
//echo "1日前"   . date("Y/m/d", strtotime("2007/12/20 -1 day"  ));
//echo "1ヶ月前" . date("Y/m/d", strtotime("2007/12/20 -1 month"));
//echo "1年前"   . date("Y/m/d", strtotime("2007/12/20 -1 year" ));
//echo "1週間前" . date("Y/m/d", strtotime("2007/12/20 -1 week" ));
function nowDate()
{
    static $now;
    if (is_null($now)) {
        $now = time();
    }
    return $now;
}
function getNow($format)
{
    $now = nowDate();
    return date($format, $now);
}
// $baseDate yyyy/mm/dd
// $add      -1 day  -1 month
function getDateCalc($format, $baseDate, $add)
{
    return date($format, strtotime($baseDate ." ".$add));
}
function validation($value, $rule)
{
    $rules = explode('|', $rule);
    foreach ($rules as $r) {
        switch (trim($r)){
            case 'required':
                if (is_null($value)) {
                    return ['result' => false, 'message' => '必ず入力してください'];
                }
                break;
            case 'number':
                if (!is_null($value) && is_string($value)) {
                    return ['result' => false, 'message' => '数値を入力してください'];
                }
                break;
        }
    }
    return ['result' => true, 'message' => ''];
}

