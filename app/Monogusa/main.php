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
    if ($isError) {
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
function responseJson($ary = [], $status = 200)
{
    header('Content-Type: application/json; charset=utf-8');
    if ($status != 200) {
        http_response_code($status);
    }
    if (is_array($ary) || is_object($ary)) {
        echo json_encode($ary);
    } else {
        echo $ary;
    }
}
function getHeader($name)
{
    $name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    if (isset($_SERVER[$name])) {
        return $_SERVER[$name];
    }
    return false;
}
function Rt($method, $url, $callMethod = null, $authCheck = true) {
    static $routeMap;
    if (is_null($routeMap)) {
        $routeMap           = [];
        $routeMap['GET']    = [];
        $routeMap['POST']   = [];
        $routeMap['PUT']    = [];
        $routeMap['DELETE'] = [];
    }
    // ルート設定
    if (!is_null($callMethod)) {
        $routeMap[$method][$url] = [
            'method'    => $callMethod,
            'authCehck' => $authCheck,
        ];
        return;
    }
    // routing
    foreach ($routeMap[$method] as $key => $value) {
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
            if ($value['authCehck']) {
                if (!authCheck()) {
                    header('Location: ' . WEB_ROOT . '/login');
                    exit();
                }
            }
            if (is_string($value['method'])) {
                $classInfo = explode('::', $value['method']);
                if (count($classInfo) == 2) {
                    require_once 'app/Controllers/' . $classInfo[0] .'.php';
                    if (!class_exists($classInfo[0])) {
                        Log::error('ファイル名とクラス名が違います。');
                        throw new \Exception("class not defined.");
                    }
                    $routeClass = new $classInfo[0];
                    $methodName = $classInfo[1];
                    call_user_func_array([$routeClass, $methodName], $methosParam);
                    return;
                }
            } else {
                call_user_func_array($value['method'], $methosParam);
            }
            return;
        }
    }
    http_response_code(404);
    echo json_encode(['message' => 'Not Found!']);
}
function authCheck()
{
    return getSession('is_login', false);
}
function redirect($url)
{
    header('Location: ' . $url);
}
function RtGET($url, $callMethod = null, $authCheck= true)
{
    Rt('GET', $url, $callMethod, $authCheck);
}
function RtPOST($url, $callMethod = null, $authCheck= true)
{
    Rt('POST', $url, $callMethod, $authCheck);
}
function RtPUT($url, $callMethod = null, $authCheck= true)
{
    Rt('PUT', $url, $callMethod, $authCheck);
}
function RtDELETE($url, $callMethod = null, $authCheck= true)
{
    Rt('DELETE', $url, $callMethod, $authCheck);
}
function run()
{
    $route = str_replace(WEB_ROOT,'',REQUEST_URL);
    if ($route == '') {
        $route = '/';
    }
    $route = explode('?', $route)[0];
    header('Access-Control-Allow-Origin: *');
    if (REQUEST_METHOD == 'OPTIONS') {
        //header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Headers: Origin, Authorization, Accept, Content-Type, _token, firstOpenedAt");
            
        return;
    } else {
        //header("Access-Control-Allow-Origin: *");
    }
    request();
    Log::access(REQUEST_METHOD . ' '. REQUEST_URL . ' ' . clientIp());
    if (AUTH) {
        $token = md5(clientIp() . '_' . getHeader('firstOpenedAt'));
        if (getHeader('_token') != $token) {
            http_response_code(401);
            return;
        }
    }
    Rt(REQUEST_METHOD, $route);
}

function webInIt()
{
    define("TOKEN", 'jiw9hiohjdoiifhioi4ehjkjareqr7889uhgihs');
    // REQUEST_METHOD
    define("REQUEST_METHOD", $_SERVER["REQUEST_METHOD"]);

    $protocol = isset($_SERVER["https"]) ? 'https' : 'http';
    $domain =  $protocol . '://' . $_SERVER['HTTP_HOST'];
    // WEB_ROOT
    $subDir = '';
    define("WEB_ROOT", $domain. $subDir);
    // REQUEST_URL
    $requestUrl = $domain . $_SERVER['REQUEST_URI'];
    define("REQUEST_URL", $requestUrl);

    $url_path = ltrim(str_replace(WEB_ROOT, '', REQUEST_URL), '/');
    define("URL_PATH", $url_path);

    $route = ltrim(str_replace('//', '/', $url_path), '/');
    $route = preg_replace('/\?.*?$/', '', $route);
    if ($route === '') {
        $route = 'index';
    }
    $routes = explode('/', $route);
    if ($routes[0] == 'auth') {
        define("AUTH", true);
    } else {
        define("AUTH", false);
    }
    
}
function request($key = null)
{
    if (is_null($key)) {
        return null;
    }
    static $request;
    if (is_null($request)) {
        if ($_SERVER["REQUEST_METHOD"] == "PUT") {
            $putData = file_get_contents('php://input');
            $putData = explode("&", $putData);
            $request = [];
            foreach ($putData as $value) {
                $row = explode("=", $value);
                $request[$row[0]] = $row[1];
            }
        } else {
            $request = $_GET + $_POST;
        }
    }
    Log::info($request);
    
    return $request[$key];
}
function getRequestJson()
{
    static $json;
    if (is_null($json)) {
        $json = json_decode(file_get_contents('php://input'), true);
    }
    return $json;
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
function storageDir()
{
    return 'storage';
}
function viewDir()
{
    return 'app/view';
}
function frameworkCacheDir()
{
    return 'storage/cache/framework';
}
function execScheduler($schedules)
{
    $infoDir = CONSOLE_PATH . '/' . frameworkCacheDir();

    $execDate = execDate();

    foreach ($schedules as $schedule) {
        $setting        = explode(',', $schedule);
        $batchName      = $setting[0];
        $schduleSetting = $setting[1];
    
        $fileName =str_replace([',', ':'], '_', $schedule);
        $infoPath = $infoDir . '/' . $fileName . '.txt';
    
        // 指定の時間か
        if (!scheduledTimeCheck($batchName, $schduleSetting, $infoPath)) {
            continue;
        }
    
        // 実行
        $commnad = CONSOLE_PATH . '/console ' . $batchName;
        if (strpos(PHP_OS, 'WIN')!==false) {
            //「start」コマンドで非同期実行
            $fp = popen('start php ' . $commnad, 'r');
            pclose($fp);
        } else {
            exec('php '.$commnad.' > /dev/null &');
        }
        file_put_contents($infoPath, EXEC_TIME);
        //if (true) {
        //    exec("nohup php -c '' '起動PHPファイルパス' '引数ARGS' > /dev/null &");
        //}
    }
}
function scheduledTimeCheck($batchName, $schduleSetting, $infoPath)
{
    $execDate = execDate();

    $lastExecuted = '20181231235959';
    // 初めて実行する
    if (file_exists($infoPath)) {
        $lastExecuted = file_get_contents($infoPath);
    }

    if ($schduleSetting == 'every:1') {
        return true;
    }

    $setting = explode(':', $schduleSetting);
    $num     = intval($setting[1]);
    switch ($setting[0]){
        case 'every':
            if ((intval($execDate['i']) % $num) == 0) {
                return true;
            }
            break;
        case 'hourly':
    }

    return false;
}
function loadPart($baseText)
{
    $viewDir = viewDir();
    if (preg_match_all("/@parts\(.*?\)/",$baseText,$vals)) {
        foreach ($vals[0] as $val) {
            $targetView = str_replace("')",'',str_replace("@parts('",'',$val));
            $part = file_get_contents($viewDir.'/' . $targetView . '.template');
            $baseText = str_replace($val, $part, $baseText);
        }
    }
    return $baseText;
}
function getParamFromTextMethod($methodName, $text) {
    if (preg_match_all("/".$methodName."\(\'.*?\'\)/",$text,$vals)) {
        foreach($vals[0] as $val) {
            $targetView = str_replace("')",'',str_replace($methodName."('",'',$val));
            return [$val, $targetView];
        }
    }
    return null;
}
function loadParentPage($baseText, $functionName, $replaceName)
{
    $viewDir = viewDir();
    $ret = getParamFromTextMethod('@'.$functionName, $baseText);
    if (is_null($ret)) {
        return $baseText;
    } else {
        $fileName = $ret[1];
        $master = file_get_contents($viewDir.'/' .$fileName. '.template');
        $main = str_replace($ret[0], '', $baseText);
        $html = str_replace('@'. $replaceName, $main, $master);
        return $html;
    }
}
function makeCache($name)
{
    $makeCache = false;
    $viewDir = viewDir();
    $cacheDir = 'storage/cache';
    $viewPath = $viewDir.'/'.$name.'.template';
    if (!file_exists($viewPath)) {
        throw new Exception("view not found : ". $viewPath);
    }
    $cachePath = $cacheDir.'/'.$name.'.php';
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

    if (!$makeCache) {
        return;
    }

    // ここからcache作成
    $names = explode('/', $name);
    if (count($names) == 2) {
        makeFolderIfNotExists($cacheDir . '/' . $names[0]);
    }

    $template = file_get_contents($viewDir . '/' . $name . '.template');
    $template = loadParentPage($template, 'load_layout', "place('content')");
    $template = loadParentPage($template, 'load_master', "place('content')");
    $template = loadPart($template);

    $before = [];
    $after  = [];

    // js
    $js = '';
    if (preg_match_all('/@js.*?@endjs/s', $template, $vals)) {
        foreach($vals[0] as $val) {
            $before[] = $val;
            $after[]  = '';

            $js .= str_replace('@endjs', '', str_replace('@js','',$val));
        }
    }
    $before[] = "@place('js')";
    $after[]  = $js;

    // style
    $css = '';
    if (preg_match_all('/@css.*?@endcss/s', $template, $vals)) {
        foreach($vals[0] as $val) {
            $before[] = $val;
            $after[]  = '';

            $css .= str_replace('@endcss', '', str_replace('@css','',$val));
        }
    }
    $before[] = "@place('css')";
    $after[]  = $css;

    // if
    if (preg_match_all('/@\{if.*?\}/', $template, $vals)) {
        foreach($vals[0] as $val) {
            $before[] = $val;
            $after[]  = '<?php '. str_replace('}', '', str_replace('@{','',$val)).': ?>';
        }
    }

    $before[] = '@{else}';
    $after[]  = '<?php else: ?>';

    $before[] = '@{endif}';
    $after[]  = '<?php endif; ?>';

    // foreach
    if (preg_match_all('/@\{foreach.*?\}/', $template, $vals)) {
        foreach($vals[0] as $val) {
            $before[] = $val;
            $after[]  = '<?php '. str_replace('}', '', str_replace('@{','',$val)).': ?>';
        }
    }

    $before[] = '@{endforeach}';
    $after[]  = '<?php endforeach ?>';

    // echo
    if (preg_match_all('/@\(.*?\)/', $template, $vals)) {
        foreach($vals[0] as $val) {
            $before[] = $val;
            $after[]  = '<?php echo $'. str_replace(')', '', str_replace('@(','',$val)).'; ?>';
        }
    }

    $template = str_replace($before, $after, $template);
    file_put_contents('storage/cache/' . $name . '.php', $template);
}
function setting($key)
{
    global $setting;
    if (!array_key_exists($key, $setting)) {
        throw new \Exception('key not exists.');
    }
    return $setting[$key];
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
function siteTitle($setTitle = '')
{
    static $title;
    if ($setTitle == '') {
        return is_null($title) ? setting('SITE_TITLE') : $title;
    } else {
        $title = $setTitle;
    }
}
function render($name, $params = null, $statusCode = null)
{
    //global $TITLE,$HEAD,$META;
    $TITLE = siteTitle();
    $HEAD  = '';
    $META = '';
    if (!is_null($statusCode)) {
        http_response_code($statusCode);
    }

    makeCache($name);

    if (!array_key_exists('menu_show', $params)) {
        $params['menu_show'] = true;
    }

    foreach ($params as &$param) {
        $param = str_replace('@(WEB_ROOT)', WEB_ROOT, $param);
    }
    if (!is_null($params) && count($params) > 0) {
        extract($params);
    }
    $WEB_ROOT    = WEB_ROOT;
    //$TOKEN       = getSession('_token','');
    $TOKEN       = '';
    $REQUEST_URL = REQUEST_URL;

    if (setting('NO_CACHE')) {
        $NO_CACHE    =  '?var=' . makeRandStr(8);
    } else {
        $NO_CACHE    =  '';
    }

    //$human_check = humanCheck();
    ob_start();
    include('storage/cache/' . $name . '.php');
    $view = ob_get_contents();
    ob_get_clean();
    echo $view;
}
function getFileLists($folder, $order = 0, &$maxCnt = -1, &$startCnt = 0, &$cnt = 0)
{
    $files = scandir($folder, $order);
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
function makeRandStr($length = 30)
{
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

function toPlural($in)
{
    $dic = [
        "dictionary" => "dictionaries",
        "fox"        => "foxes",
        "dish"       => "dishes",
        "watch"      => "watches",
        "gentleman"  => "gentlemen",
        "leaf"       => "leaves",
        "radio"      => "radios",
        "class"      => "classes",
        "knife"      => "knives",
        "foot"       => "feet"
    ];
    
    // 不規則変化の辞書に存在する場合は優先して変換
    if (array_key_exists($in, $dic)) {
        return $dic[$in];
    } else {
        $tmpStr = $in;
        
        // 規則のある文字を置換
        // 1. 末尾が「s、sh、ch、o、x」の場合は「es」をつける
        $tmpStr = preg_replace("/(s|sh|ch|o|x)$/","$1es",$tmpStr);
        
        // 2. 末尾が「f、fe」の場合は「ves」に置き換える
        $tmpStr = preg_replace("/(f|fe)$/","ves",$tmpStr);
        
        // 3. 末尾二文字が「母音 + y」の場合は「s」をつける
        $tmpStr = preg_replace("/(a|i|u|e|o)y$/","$1ys",$tmpStr);
        
        // 4. 3でマッチングしたもの以外で末尾が「y」の場合は「ies」に置き換える
        $tmpStr = preg_replace("/y$/","ies",$tmpStr);
        
        // 5. マッチングしなかったものの末尾に「s」を付ける
        if (!preg_match("/s$/",$tmpStr)) {
            $tmpStr = $tmpStr."s";
        }
        return $tmpStr;
    }
}

function modelInclude()
{
    $files = getFileLists('app/Models');
    foreach ($files as $file) {
        require_once $file;
    }
}
function serviceInclude()
{
    $files = getFileLists('app/Services');
    foreach ($files as $file) {
        require_once $file;
    }
}
function getY($ymdhis)
{
    return substr($ymdhis, 0, 4);
}
function getYm($ymdhis)
{
    return substr($ymdhis, 0, 6);
}
function getYmd($ymdhis)
{
    return substr($ymdhis, 0, 8);
}
function getYmdH($ymdhis)
{
    return substr($ymdhis, 0, 10);
}
function getYmdHi($ymdhis)
{
    return substr($ymdhis, 0, 12);
}
function execDate()
{
    static $execDate;
    if (is_null($execDate)) {
        $execDate = [
            'Y' => substr(EXEC_TIME, 0, 4),
            'm' => substr(EXEC_TIME, 4, 2),
            'd' => substr(EXEC_TIME, 6, 2),
            'H' => substr(EXEC_TIME, 8, 2),
            'i' => substr(EXEC_TIME, 10, 2),
            's' => substr(EXEC_TIME, 12, 2),
        ];
    }
    return $execDate;
}
function isCommandLineInterface()
{
    return (php_sapi_name() === 'cli');
}
function makeFolderIfNotExists($path)
{
    if (!file_exists($path)) {
        mkdir($path, '0777', true);
    }
}

define('EXEC_TIME', date("YmdHis"));

use Illuminate\Database\Capsule\Manager as Capsule;
try {
    if (!isCommandLineInterface()) {
        session_name("7CXziwojoiiejqji899h84hcb8");
        session_start();
    }

    if (setting('DB_USE')) {
        $capsule = new Capsule;
        $capsule->addConnection(setting('DB'));

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        modelInclude();
    }

    serviceInclude();

    if (!isCommandLineInterface()) {
        webInIt();
        require_once 'app/routes.php';
        run();
    }
} catch (Exception $e) {
    Log::error($e->getMessage().'  '.$e->getFile().'('.$e->getLine().')');
    if (!isCommandLineInterface()) {
        http_response_code(500);
        echo json_encode(['message'=>'inernal server error!']);
    }
}


