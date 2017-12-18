<?php

function before()
{
    // access log;
    $data = request('*');
    if (array_key_exists('login_pass', $data)) {
         $data['login_pass'] = '***';
    }
    \Log::info('access : ' . REQUEST_URL . ' ' . json_encode($data));

}
function after()
{
}

function menu($name)
{
    //$menuText = file_get_contents(dataDir().'/cms/menu/'.$name.'.txt');
    $menuText = '';
    $menuAry = csvToArray($menuText, false);
    $menu = '';
    foreach ($menuAry as $row) {
        $menu .= '<div class="menu-list"><a href="@(WEB_ROOT)'.$row[1].'">' .$row[0] . '</a></div>';
    }
    return $menu;
}

function mainContents($text = null){
    static $main;
    if (is_null($main)) {
        $main = '';
    }

    if (is_null($text)) {
        return $main;
    } else {
        $main .= $text;
    }
}

function tagList($text = null){
    static $tag;
    if (is_null($tag)) {
        $tag = '';
    }

    if (is_null($text)) {
        return $tag;
    } else {
        $tag .= $text;
    }
}

