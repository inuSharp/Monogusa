<?php
ini_set( 'display_errors' , 0 );
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
        $setting = parse_ini_file(".setting", true);
    }
    if (array_key_exists($key, $setting)) {
        return $setting[$key];
    } else {
        return $default;
    }
}

$appSetting_yw84scpcDAekiiS = null;
function appSetting($key,$default=null) {
    global $appSetting_yw84scpcDAekiiS;
    if (is_null($appSetting_yw84scpcDAekiiS)) {
        $appSetting_yw84scpcDAekiiS = json_decode(file_get_contents(dataDir()."/system/appSetting.json"), true);
    }
    if (array_key_exists($key, $appSetting_yw84scpcDAekiiS)) {
        return $appSetting_yw84scpcDAekiiS[$key];
    } else {
        return $default;
    }
}
function addAppSetting() {
    global $appSetting_yw84scpcDAekiiS;
}

function checkWindows()
{
    if (DIRECTORY_SEPARATOR == '\\') {
        return true;
    } else {
        return false;
    }
}

function csvToArray($csv,$existsHeader = true)
{
    $lines = explode("\n",preg_replace("/^(\s)*(\r|\n|\r\n)/m", "", $csv));

    $start = 0;
    if ($existsHeader) {
        $header = explode(',', $lines[0]);
        $start = 1;
    }

    $cnt = 0;
    $ret = [];
    for ($i=$start;$i<count($lines);$i++) {
        if ($lines[$i] == '') {
            continue;
        }
        $cnt++;
        $line = explode(',', $lines[$i]);
        if ($existsHeader) {
            $ret[$cnt] = [];
            $colIndex = -1;
            foreach ($header as $name) {
                $colIndex++;
                $ret[$cnt][$name] = $line[$colIndex];
            }
        } else {
            $ret[$cnt] = $line;
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
$lock_yw84scpcDAekiiS = null;
function getLock($fileName)
{
    global $lock_yw84scpcDAekiiS;
    $lockFilePath = dataDir().'/system/'. $fileName . '.lock';
    if (!file_exists($lockFilePath)) {
        getLock('system');
        try {
            file_put_contents($lockFilePath, '');
        } catch (\Exception $ex) {
            throw $ex;
        } finally {
            releaseLock();
        }
    }
    $lock_yw84scpcDAekiiS = fopen($lockFilePath, 'r');
    $cnt = 0;
    while (true) {
        if ($cnt > 10) {
            throw new \Exception();
            break;
        }
        if (!flock($lock_yw84scpcDAekiiS, LOCK_EX)){
            $cnt++;
            sleep(1);
            continue;
        }
        break;
    }
}
function releaseLock($lockFileName = '')
{
    global $lock_yw84scpcDAekiiS;
    if (is_null($lock_yw84scpcDAekiiS)) {
        return;
    }
    flock($lock_yw84scpcDAekiiS, LOCK_UN);
    fclose($lock_yw84scpcDAekiiS);
    if ($lockFileName != '') {
        @unlink(dataDir().'/system/'.$lockFileName.'.lock');
    }
    $lock_yw84scpcDAekiiS = null;
}

function filePutWithLock($filePath, $contents)
{
    $lockFile = str_replace(['/', '.'], '', $filePath);
    getLock($lockFile);
    try {
        file_put_contents($filePath, $contents, FILE_APPEND | LOCK_EX);
    } catch (\Exception $ex) {
        throw $ex;
    } finally {
        releaseLock($lockFile);
    }
}
function commonAdminStyle()
{
    global $TITLE,$HEAD,$JS,$STYLE;
    $STYLE = <<<STYLE
        .grid {
          max-width: 1000px;
        }
        .grid-pad {
          padding-left: 4px; /* grid-space to left */
          padding-right: 4px; /* grid-space to right: (grid-space-left - column-space) e.g. 20px-20px=0 */
        }
STYLE;
}
function adminMenu()
{
    $html = <<<HTML
        
HTML;
}
function makeMessageList($page = 1)
{
    if (!file_exists(dataDir(). '/' . 'message/message.txt')) {
        return '';
    }
    $text = file_get_contents(dataDir(). '/' . 'message/message.txt');
    $lists = explode('----------end of message', $text);
    $lists = array_reverse($lists);
    $messages = '';
    $onePageCount = 200;
    $onePageCount = 20001;
    $start = $onePageCount * $page - $onePageCount;
    for ($i=$start;$i<$start+$onePageCount;$i++) {
        if (!array_key_exists($i, $lists)) {
            break;
        }
        if (str_replace([' ', "\n"], '', $lists[$i]) == '') {
            continue;
        }
        $messages .= '<div style="margin-bottom:10px;"><pre>' . $lists[$i] . '</pre></div>';
    }
    return $messages;
}
function accessCounter()
{
    $ymdHis = date("YmdHis");
    $ymd = substr($ymdHis, 0, 8);
    $his = substr($ymdHis, 8, 6);
    $ym = substr($ymd, 0, 6);
    $key = clientIp() . ' ' . REQUEST_URL . ' ' . $his;
    $fileName = $ymd;
    getLock($fileName);
    try {
        $dir = dataDir().'/counter/' . $ym;
        if (!file_exists($dir)) {
            mkdir($dir, 0777);
        }
        file_put_contents($dir . '/' . $fileName,  $key . "\n", FILE_APPEND | LOCK_EX);
    } catch (\Exception $ex) {
        throw $ex;
    } finally {
        releaseLock();
    }
}
function sendDownload($filename, $path) {
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$filename".";");
    header("Content-Transfer-Encoding: binary");
    return readfile($path);
}
function xml($path)
{
    $xml = simplexml_load_file($path);
    return get_object_vars($xml);
}

function projectDir()
{
    $dir = realpath("");
    if (checkWindows()) {
        $dir = preg_replace('/^.*?:/', '', $dir);
        $dir = str_replace('\\', '/', $dir);
    }
    $dir = rtrim($dir, '/');
    return $dir;
}
function codeDir()
{
    return projectDir().'/Code';
}
function viewDir()
{
    return projectDir().'/view';
}
function dataDir()
{
    return projectDir().'/data';
}
function imageDir()
{
    return projectDir().'/assets/img';
}
function makehash()
{
    return md5(setting('system','app_key').'oZwp7aOQDpK1Lag');
}
function cipher($input,$type)
{
    $td  = mcrypt_module_open('tripledes', '', 'ecb', '');
    $key = substr(makehash(), 0, mcrypt_enc_get_key_size($td));
    $iv  = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
    if (mcrypt_generic_init($td, $key, $iv) < 0) {
        throw new Exception("encrypt error");
    }
    if ($type == 1) {
        $str = base64_encode(mcrypt_generic($td, $input));
    } else {
        $str = rtrim(mdecrypt_generic($td, base64_decode($input)));
    }
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);
    return $str;
}

function setSession($key, $value)
{
    $_SESSION[$key] = $value;
    if ($value === '') {
        $value = '<blank>';
    }
    if ($value === true) {
        $value = 'true';
    } else if ($value === false) {
        $value = 'false';
    }
    Log::debug("set session key:".$key."    value:".$value);
}
function getSession($key, $default = null)
{
    if (array_key_exists($key, $_SESSION)) {
        return $_SESSION[$key];
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

function redirect($url)
{
    header('Location: ' . $url);
    exit();
}
function changeResponseCode($code)
{
    http_response_code($code);
}
function saveUploadFile($htmlName, $imageDir)
{
    $fileNameExt = '';
    if(isset($_FILES[$htmlName])) {
        if (is_uploaded_file($_FILES[$htmlName]["tmp_name"])) {
            $fileName = makeUniqueFileName($imageDir);
            $fileInfo = pathinfo($_FILES[$htmlName]["name"]);
            $fileNameExt = $fileName . '.'. $fileInfo['extension'];
            if (move_uploaded_file($_FILES[$htmlName]["tmp_name"], $imageDir . "/" . $fileNameExt)) {
                chmod($imageDir . "/" . $fileNameExt, 0644);
            } else {
                throw new Exception('ファイルを保存できませんでした');
            }
        } else {
            throw new Exception('ファイルを取得できませんでした');
        }
    }
    return $fileNameExt;
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


// request data
function request($name,$defaultValue = null)
{
    static $request;
    if (is_null($request)) {
        $request = array_merge($_GET, $_POST);
        parse_str(file_get_contents('php://input'), $put_param);
        if ($put_param != null) {
            $request += $put_param;
        }
    }
    if (array_key_exists($name, $request)) {
        return $request[$name];
    } else {
        return $defaultValue;
    }
}
function loginCheck()
{
    if (LOGIN_FLG) {
        header('Location: ' . WEB_ROOT . '/');
        exit();
    }
}
function guestCheck()
{
    if (!LOGIN_FLG) {
        return false;
    } else {
        return true;
    }
}
function clientIp()
{
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}
function route($method, $url, $callMethod = null) {
    static $route_map;
    if (is_null($route_map)) {
        $route_map           = [];
        $route_map['GET']    = [];
        $route_map['POST']   = [];
        $route_map['PUT']    = [];
        $route_map['DELETE'] = [];
    }
    if ($callMethod == null) {
        foreach ($route_map[$method] as $key => $value) {
            $methosParam = [];
            $routeMatch = true;
            $route      = explode('/',ltrim($key, '/'));
            $requestUrl = explode('/',ltrim($url, '/'));
            if (count($route) != count($requestUrl)) {
                continue;
            }
            foreach ($route as $index => $routeName) {
                if (substr($routeName, 0, 1) == ':') {
                    $methosParam[] = $requestUrl[$index];
                } else if ($routeName != $requestUrl[$index]) {
                    $routeMatch = false;
                    break;
                }
            }
            if ($routeMatch) {
                call_user_func_array($value, $methosParam);
                return;
            }
        }
        http_response_code(404);
        echo 'Not Found!';
    } else {
        $route_map[$method][$url] = $callMethod;
    }
}
function run()
{
    $route = str_replace(WEB_ROOT,'',REQUEST_URL);
    if ($route == '') {
        $route = '/';
    }
    $route = explode('?', $route)[0];
    route(REQUEST_METHOD,$route);
}

$JS    = '';
$STYLE = '';
$TITLE = '';
$HEAD  = '';
function getParamFromTextMethod($methodName, $text) {
    if (preg_match_all("/".$methodName."\(\'.*?\'\)/",$text,$vals)) {
        foreach($vals[0] as $val) {
            $targetView = str_replace("')",'',str_replace($methodName."('",'',$val));
            return [$val, $targetView];
        }
    }
    return null;
}
function getFileLists($folder) {
    $files = scandir($folder, 0 ); // 1:降順
    $lists = [];
    foreach($files as $file) {
        if ($file == ".." || $file == ".") {continue;}

        if (is_dir($folder.'/'.$file)) {
            $lists = array_merge($lists, getFileLists($folder.'/'.$file));
            continue;
        }
        $lists[] = $folder.'/'.$file;
    }
    
    return $lists;
}
function getViewsLatestTime($viewDir) {
    $lists = getFileLists($viewDir);
    $maxTime = 0;
    foreach ($lists as $list) {
        $time = filemtime($list);
        if ($maxTime < $time) {
            $maxTime = $time;
        }
    }
    return $maxTime;
}

function viewParamReplace($html, $params)
{
    global $TITLE,$HEAD,$JS,$STYLE;

    $params['WEB_ROOT'] = WEB_ROOT;
    $params['TOKEN'] = getSession('_token');
    $params['TITLE'] = $TITLE;
    $params['HEAD'] = $HEAD;
    $params['JS'] = $JS;
    $params['STYLE'] = $STYLE;
    $params['NO_CACHE'] = '?var=' . makeRandStr(5);
    $params['AT'] = '@';
    $repBef = [];
    $repAft = [];
    foreach ($params as $name => $param) {
        $repBef[] = '@(' . $name . ')';
        $repAft[] = $param;
    }
    $html = str_replace($repBef, $repAft, $html);
    if (preg_match_all('/@\(.*?\)/',$html,$vals)) {
       foreach($vals[0] as $val) {
           echo 'undefined view parameter \'' . str_replace(')','',str_replace('@(','',$val)) . '\'<br/>';
       }
       throw new Exception("undefined view parameter");
    }
    if (preg_match_all('/@esc\(.*?\)esc@/ms',$html,$vals)) {
        foreach($vals[0] as $val) {
            $rep = str_replace(['<','>'],['&lt;','&gt;'],$val);
            $html = str_replace($val,$rep, $html);
        }
    }
    $html = str_replace(['ATMARK','@esc(',')esc@'],['@','',''], $html);
    return $html;
}

function render($name, $params = []) {
    $makeCache = false;
    $viewDir = viewDir();
    $cacheDir = dataDir().'/system/cache';
    $viewPath = $viewDir.'/'.$name.'.template';
    if (!file_exists($viewPath)) {
        throw new Exception("view not found");
    }
    $cachePath = $cacheDir.'/'.$name.'.template';
    if (file_exists($cachePath)) {
        $viewFileTime = getViewsLatestTime($viewDir);
        $cacheFileTime = filemtime($cachePath);

        if ($cacheFileTime > $viewFileTime) {
            $html = file_get_contents($cachePath);
        } else {
            $makeCache = true;
        }
    } else {
        $makeCache = true;
    }

    if ($makeCache) {
        $main = file_get_contents($viewPath);
        $main = loadParentPage($main, 'load_layout', 'load_place');
        $html = loadParentPage($main, 'load_master', 'load_place');

        file_put_contents($cachePath, $html);
    }
    echo viewParamReplace($html, $params);
}

function loadParentPage($baseText, $functionName, $replaceName)
{
    $viewDir = viewDir();
    $ret = getParamFromTextMethod('@'.$functionName, $baseText);
    if (is_null($ret)) {
        return $baseText;
    } else {
        $fileName = str_replace('load_', '', $functionName) . '/' . $ret[1];
        $master = file_get_contents($viewDir.'/' .$fileName. '.template');
        $main = str_replace($ret[0], '', $baseText);
        $html = str_replace('@'. $replaceName . '()', $main, $master);
        return $html;
    }
}

$errorFlg_yw84scpcDAekiiS   = false;
function response($useRoute = true)
{
    global $errorFlg_yw84scpcDAekiiS;
    $users = appSetting('users');
    if (REQUEST_METHOD == 'GET' && setting('use_management', false) && count($users) == 0) {
        render('new_user', []);
    } else if ($useRoute && !$errorFlg_yw84scpcDAekiiS) {
        run();
    } else {
        \Log::info('response else');
    }
}

function responseJson($ary = [], $status=200)
{
    header('Content-Type: application/json; charset=utf-8');
    if ($status != 200) {
        http_response_code($status);
    }
    if (is_array($ary)) {
        echo json_encode($ary);
    } else {
        echo $ary;
    }
}

$AUTOLOAD_JAVASCRIPT = <<<JS
  var cnt=0;
  function getData()
  {
      $.ajax({
          type: 'GET',
          url: SERVER + '/api/list',
      }).done(function(data){
          document.getElementById("lists").innerHTML = document.getElementById("lists").innerHTML + data;
          autoload();
      }).fail(function() {
          alert('error!');
      }).always(function() {
      });
  }
  function autoload()
  {
    var btm = document.getElementById('btm');
    // 末尾の要素の上端の位置
    var lastElemTop = btm.offsetTop - document.documentElement.clientHeight;
    if(window.pageYOffset >= lastElemTop)
    {
        console.log("autoload");
        getData();
    } else {
      console.log("else");
      setTimeout(autoload,1500);
    }
  }
  //autoload();
JS;

function debugInfo()
{

}
function debug($text1, $text2 = '')
{
    if (!defined('DEBUG') || DEBUG == false) {
        return;
    }
    $text = $text1;
    if ($text === true) {
        $text = 'true';
    }else if ($text === false) {
        $text = 'false';
    }
    if ($text2 === true) {
        $text2 = 'true';
    }else if ($text2 === false) {
        $text2 = 'false';
    }
    if ($text2 !== '') {
        $text = $text  . ' : ' . $text2;
    }
    if (!API_FLG) {
        echo $text . '<br/>';
    }
}
function errorPage($message)
{
    render('contents', ['contents'=>$message]);
}

class Log
{
    public static $logDir = 'data/system/log';
    public static function getFilePath()
    {
        if (!file_exists(self::$logDir)) {
            if(mkdir(self::$logDir, 0777)){
                //作成したディレクトリのパーミッションを確実に変更
                chmod(self::$logDir, 0777);
            }
        }
        return self::$logDir . '/' . date('Y-m-d') . '.log';
    }
    public static function info($s)
    {
        self::write('INFO : '.$s, self::getFilePath());
    }
    public static function error($s)
    {
        self::write('ERROR: '.$s, self::getFilePath());
    }
    public static function debug($s)
    {
        if (defined('DEBUG') && DEBUG == true) {
            self::write('DEBUG: '.$s, self::getFilePath());
        }
    }
    public static function write($s,$path)
    {
        self::rotate();
        $s = '[' . date('Y-m-d H:i:s') . ']' . ' ' . $s;
        file_put_contents($path, $s . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function rotate()
    {
        foreach(glob(self::$logDir.'/*.log') as $file){
            $logdate = str_replace(['.log','-'], '', basename($file));
            if ($logdate == date('Ymd')) {
                continue;
            }
            $ym      = substr($logdate,0,6);
            $backUpDir = self::$logDir.'/'.$ym;
            if (!file_exists($backUpDir)) {
                if(mkdir($backUpDir, 0777)){
                    chmod($backUpDir, 0777);
                }
            }
            rename($file, $backUpDir . '/' . basename($file));
        }
    }
}

function webInIt()
{
    if (setting('session_path', '') != '') {
        session_save_path(setting('session_path'));
    }
    if (!file_exists(dataDir()."/system/appSetting.json")) {
      $appSettingFormat = [];
      $appSettingFormat['appKey'] = makeRandStr(4);
      $appSettingFormat['admin_path'] = "/monogusa_admin";
      $appSettingFormat['users'] = [];
      file_put_contents(dataDir()."/system/appSetting.json",json_encode($appSettingFormat));
    }
    session_name("7CXziwo".appSetting('appKey')."cb8");
    session_start();
    
    define("DEBUG", setting('debug'));
    // REQUEST_METHOD
    define("REQUEST_METHOD", $_SERVER["REQUEST_METHOD"]);

    $protocol = isset($_SERVER["https"]) ? 'https' : 'http';
    $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'];
    // WEB_ROOT
    $subDir = setting('web_sub_dir') == '' ? '' : '/' . setting('web_sub_dir');
    define("WEB_ROOT", $domain. $subDir);
    // REQUEST_URL
    $requestUrl = $domain . $_SERVER['REQUEST_URI'];
    define("REQUEST_URL", $requestUrl);


    $url_path = ltrim(str_replace(WEB_ROOT, '', REQUEST_URL), '/');
    define("URL_PATH", $url_path);

    // http://host/auth/api/test
    // http://host/auth/test
    // http://host/api/test
    // http://host/test


    // 認証
    if (strstr(URL_PATH, "auth")) {
        define("AUTH_FLG", true);
        $url_path = str_replace('auth', '', $url_path);
    } else {
        define("AUTH_FLG", false);
    }

    if (strstr(URL_PATH, "api")) {
        define("API_FLG", true);
        $url_path = str_replace('api', '', $url_path);
    } else {
        define("API_FLG", false);
    }

    AUTH_FLG  && debug('auth', 'あり');
    !AUTH_FLG && debug('auth', 'なし');
    API_FLG && debug('api', 'true');

    $route = ltrim(str_replace('//', '/', $url_path), '/');
    $route = preg_replace('/\?.*?$/', '', $route);
    if ($route === '') {
        $route = 'index';
    }
    $routes = explode('/', $route);
    define("ROUTE", $routes[0]);
    debug('ROUTE', ROUTE);

    if (getSession('loginFlg','') == '') {
        setSession('loginFlg',false);
    }
    if (getSession('_token','') == '') {
        setSession('_token',makeRandStr());
    }
    define("LOGIN_FLG", getSession('loginFlg'));
    debug('LOGIN_FLG', LOGIN_FLG);

    $request = $_GET + $_POST;
    if ($_SERVER["REQUEST_METHOD"] == "PUT") {
        $json = file_get_contents('php://input');
        if (isset($json)) {
            $request += json_decode($json,true);
        }
    }

    if ($_SERVER["REQUEST_METHOD"] != "GET" && getSession('_token','') != request('token')) {
        throw new Exception("token miss match");
    }

    if (AUTH_FLG && !LOGIN_FLG) {
        errorPage('ログインしてください');
    }

    if (!API_FLG && !AUTH_FLG && REQUEST_METHOD == 'GET') {
        accessCounter();
    }
    setMonogusaRouting();
}

/* ログインページ・ログイン処理 */
function setMonogusaRouting()
{
    route('GET', '/'.appSetting("admin_path") . '/login', function () {
        commonAdminStyle();
        render('login', []);
    });
    // POST
    route('POST','/api/yw84scpcDAekiiS/login', function () {
        $loginID = request('login_id');
        $password = request('login_pass');

        $loginInfoMatch = false;
        $users = appSetting('users');
        foreach ($users as $user) {
            if ($loginID == $user['id'] && 
                password_verify($password, $user['password'])
            ) {
                $loginInfoMatch = true;
                break;
            }
        }

        if ($loginInfoMatch) {
            session_regenerate_id(true);
            setSession('loginFlg', 1);
            $url = '/auth' . appSetting("admin_path") . '/top';
            responseJson(["url"=>$url], 200);
            exit();
        } else {
            setSession('loginFlg', 0);
            responseJson(["message"=>"ID、もしくはパスワードが違います"], 400);
        }
    });

    // assetsフォルダを参照しようとすればエラー
    route('GET','/assets', 'assets');
    route('GET','/assets/img', 'assets');
    route('GET','/assets/js', 'assets');
    route('GET','/assets/css', 'assets');

    route('POST','/api/yw84scpcDAekiiS/add-user', function () {
        global $appSetting_yw84scpcDAekiiS;
        if (is_null($appSetting_yw84scpcDAekiiS)) {
            appSetting('test');
        }
        $loginID = request('login_id');
        $password = request('login_pass');
        $appSetting_yw84scpcDAekiiS['users'] = [];
        $appSetting_yw84scpcDAekiiS['users'][0] = ['id'=>$loginID,'password'=>password_hash($password, PASSWORD_DEFAULT)];
        file_put_contents(dataDir()."/system/appSetting.json", json_encode($appSetting_yw84scpcDAekiiS));
        responseJson(["url"=>WEB_ROOT], 200);
    });
}

function assets() {
    errorPage('Not Found');
}

