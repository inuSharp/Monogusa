<?php

// ルートのメソッドが実行される前に呼び出されます
function before()
{
    // access log;
    $data = request('*');
    if (array_key_exists('login_pass', $data)) {
         $data['login_pass'] = '***';
    }
    \Log::info('access : ' . REQUEST_URL . ' ' . json_encode($data));

}

// ルートのメソッドが実行された後に呼び出されます
function after()
{
}

