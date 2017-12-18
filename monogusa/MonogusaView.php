<?php

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
    $cacheDir = dataDir().'/cache';
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
    file_put_contents(dataDir() . '/cache/' . $name . '.php', $template);
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
function render($name, $params = null, $statusCode = null)
{
    global $TITLE,$HEAD,$META; // これがないとviewでエラーになる

    if (!is_null($statusCode)) {
        http_response_code($statusCode);
    }

    makeCache($name);
    if (!is_null($params) && count($params) > 0) {
        extract($params);
    }
    $WEB_ROOT    = WEB_ROOT;
    $TOKEN       = getSession('_token','');
    $REQUEST_URL = REQUEST_URL;
    $NO_CACHE    =  '?var=' . makeRandStr(8);
    $human_check = humanCheck();
    ob_start();
    include(dataDir() . '/cache/' . $name . '.php');
    $view = ob_get_contents();
    ob_get_clean();
    echo $view;
}

//render('view', ['aaa'=> 'testy', 'expression' => false]);
