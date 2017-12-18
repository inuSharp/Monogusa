<?php

// allow_url_fopen = On
// extension=php_openssl.dll
$useCookie = false;

function decodeMultibyteEscape($str)
{
    $decoded = preg_replace_callback('|\\\\u([0-9a-f]{4})|i', function($matched){
        return mb_convert_encoding(pack('H*', $matched[1]), 'UTF-8', 'UTF-16');
    }, $str);
    return $decoded;
}
function sendRequest($method,$url,$data=null,$json = false)
{
    global $useCookie;
    if ($json) {
        // リクエストヘッダ
        $headers = array(
            'Content-Type: application/json',
        );

        //$data = str_replace(array("\r", "\n"), '', $data);
        if (!is_null($data) && is_array($data)) {
            $data = json_encode($data);
        }
    } else {
        $headers = ['Content-Type: application/x-www-form-urlencoded'];
            
        $data = str_replace(array("\r", "\n"), '', $data);
    }

    if ($useCookie && file_exists('cookie.txt')) {
        $cookie = file_get_contents('cookie.txt');
        $headers[] = 'Cookie: '. $cookie;
    }
    $context = stream_context_create(array(
        'http' => array(
            'method' => $method,
            'header' => implode("\n", $headers),
            'ignore_errors' => true, // これ入れないと400・500番台のHTTPステータスコードが帰ってきた場合にwarning吐く
            'content' => $data
        ),
    ));

    return httpProc($url, $context);
}

function httpProc($url,$context)
{
    global $useCookie;
    if ($url == '') {
        throw new Exception('url is empty');
    }
    // $resにレスポンスボディが入る
    try {
        $res = file_get_contents($url, false, $context);
    } catch (\Exception $e) {
        \Log::error($e);
        return ['statusCode'=> -1, 'responseBody'=>$e->getMessage()];
    }

    if ($useCookie) {
        $cookies = '';
        foreach ($http_response_header as $hdr) {
            if (preg_match('/^Set-Cookie:\s*([^;]+)/', $hdr, $matches)) {
                parse_str($matches[1], $tmp);
                foreach($tmp as $key => $value) {
                    if ($cookies != '') {
                        $cookies .= '; ';
                    }
                    $cookies .= $key.'='.$value;
                }
            }
        }
        $cookies = rtrim($cookies,'; ');
        if ($cookies != '') {
            file_put_contents('cookie.txt',$cookies);
        }
    }

    // HTTPステータスコードが欲しければ$http_response_headerを使って下記でとれる
    list($version, $statusCode, $message) = explode(' ', $http_response_header[0], 3);
    return ['statusCode'=>$statusCode, 'responseBody'=>decodeMultibyteEscape(print_r($res,true))];
}

