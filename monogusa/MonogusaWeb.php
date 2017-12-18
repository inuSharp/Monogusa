<?php

function webInIt()
{
    if (setting('app_key','') == '') {
        throw new Exception("");
    }
    if (setting('session_path', '') != '') {
        session_save_path(setting('session_path'));
    }

    ini_set('session.gc_maxlifetime', 3 * 24 * 60 * 60); // 秒指定 3日
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 100); // ガベージコレクトの頻度。1なら100回に1回行う。100なら毎回

    session_name("7CXziwo".setting('app_key')."cb8");
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

    $route = ltrim(str_replace('//', '/', $url_path), '/');
    $route = preg_replace('/\?.*?$/', '', $route);
    if ($route === '') {
        $route = 'index';
    }
    $routes = explode('/', $route);
    define("ROUTE", $routes[0]);

    if (getSession('loginFlg','') == '') {
        $rememberCookieKey = "7CXziwo" . setting('app_key') . "cb9";
        if (array_key_exists($rememberCookieKey, $_COOKIE)) {
            $tokens = \Qb::select("select * from remember_tokens where token = '" . $_COOKIE[$rememberCookieKey] . "';");
            if (count($tokens) != 0) {
                setSession('loginFlg', true);
            } else {
                setSession('loginFlg', false);
            }
        } else {
            setSession('loginFlg', false);
        }
    }
    if (getSession('_token','') == '') {
        setSession('_token',makeRandStr());
    }
    define("LOGIN_FLG", getSession('loginFlg'));

    $request = $_GET + $_POST;
    if ($_SERVER["REQUEST_METHOD"] == "PUT") {
        $json = file_get_contents('php://input');
        if (isset($json)) {
            $request += json_decode($json,true);
        }
    }

    if ($_SERVER["REQUEST_METHOD"] != "GET" && getSession('_token','') != request('token')) {
        if (ROUTE != 'upload') {
            throw new Exception("token miss match");
        }
    }

    if (AUTH_FLG && !LOGIN_FLG) {
        errorPage('ログインしてください');
        throw new Exception("unauthorized");
    }

    if (!API_FLG && !AUTH_FLG && REQUEST_METHOD == 'GET') {
        accessCounter();
    }
    setMonogusaRouting();
}
function getUserAgent()
{
    if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
        $ua = $_SERVER['HTTP_USER_AGENT'];// ユーザエージェントを取得
    } else {
        $ua = '(empty)';
    }
    return $ua;
}
// Framework機能のrouting
function setMonogusaRouting()
{
    // assetsフォルダを参照しようとすればエラー
    route('GET','/assets', 'assets');
    route('GET','/assets/img', 'assets');
    route('GET','/assets/js', 'assets');
    route('GET','/assets/css', 'assets');
}
function assets() {errorPage('Not Found');}
function sendDownload($filename, $path) {
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$filename".";");
    header("Content-Transfer-Encoding: binary");
    return readfile($path);
}

function projectDir()
{
    $dir = rtrim(str_replace('monogusa', '', __DIR__), '/');
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
function publicDir()
{
    return projectDir().'/public';
}
function imageDir()
{
    return publicDir().'/assets/img';
}
function makehash()
{
    return md5(setting('monogusa','app_key').'oZwp7aOQDpK1Lag');
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
function accessCounter()
{
    if (REQUEST_URL == WEB_ROOT . '/favicon.ico') {
        return;
    }
    $ymdHis = date("YmdHis");
    $ymd = substr($ymdHis, 0, 8);
    $his = substr($ymdHis, 8, 6);
    $ym = substr($ymd, 0, 6);
    $url = URL_PATH == '' ? '/' : URL_PATH;
    if ($url[0] == '?') {
        $url = '/' . $url;
    }
    $key = clientIp() . ' ' . $url . ' ' . str_replace(' ', '',getUserAgent()) . ' ' . $his;
    $fileName = $ymd;
    try {
        $dir = dataDir().'/counter/' . $ym;
        for ($i=0; $i<3;$i++) {
            if (!file_exists($dir)) {
                try {
                    mkdir($dir, 0777);
                } catch (Exception $ex) {
                    if ($i == 2) {
                        throw $ex;
                    } else {
                        sleep(1);
                    }
                }
            } else {
                break;
            }
        }
        file_put_contents($dir . '/' . $fileName,  $key . "\n", FILE_APPEND | LOCK_EX);
    } catch (\Exception $ex) {
        throw $ex;
    }
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
    if ($name == '*') {
        return $request;
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

/**
 * Router
 */
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
                if (file_exists(dataDir().'/cache/mainte')) {
                    render('mainte');
                    return;
                } else {
                    call_user_func_array($value, $methosParam);
                    after();
                    return;
                }
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

$errorFlg_yw84scpcDAekiiS   = false;
function response($useRoute = true)
{
    global $errorFlg_yw84scpcDAekiiS;
    if ($useRoute && !$errorFlg_yw84scpcDAekiiS) {
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

function errorPage($message)
{
    render('contents', ['contents'=>$message]);
}

class Log
{
    public static $logDir = '';
    public static $rotated  = false;
    public static function getFilePath()
    {
        if (self::$logDir == '') {
            self::$logDir = dataDir() . '/log';
        }
        if (!file_exists(self::$logDir)) {
            echo self::$logDir . "\n";
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

    private static function rotate()
    {
        // 1リクエストで1回
        if (self::$rotated) {
            return;
        }
        self::$rotated = true;

        // 日をまたぐ間はろーてーとしない
        $nowTime = date('His');
        $time = intval(date('His'));
        // 0:05:00以下もしくは 23:55:00以上
        if ($time < 500 || 235500 < $time) {
            return;
        }

        foreach(glob(self::$logDir.'/*.log') as $file){
            $logdate = str_replace(['.log','-'], '', basename($file));
            // 今日のログならしない
            if ($logdate == date('Ymd')) {
                continue;
            }
            try {
                $ym      = substr($logdate,0,6);
                $backUpDir = self::$logDir.'/'.$ym;
                if (!file_exists($backUpDir)) {
                    if(mkdir($backUpDir, 0777)){
                        chmod($backUpDir, 0777);
                    }
                }
                rename($file, $backUpDir . '/' . basename($file));
            } catch(\Exception $e) {
                file_put_contents(dataDir(). 'log_error.txt', 'rotate error');
            }
        }
    }
}

