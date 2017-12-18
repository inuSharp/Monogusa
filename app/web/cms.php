<?php

route('POST','/api/message', function () {
    $text = htmlspecialchars(request('text'));
    $checkCode = request('human_check', '');
    if (!humanCheck($checkCode)) {
        responseJson(["message"=>"アルファベットが正しくありません"], 400);
        return;
    }
    $ymdHisFormat = date("Y/m/d H:i:s");
    $ymdHis       = str_replace(['/', ':', ' '], '', $ymdHisFormat);
    $ymd          = substr($ymdHis, 0, 8);
    $ip = clientIp();
    $messageSendUserFile = dataDir().'/message/'. $ymd;
    if (file_exists($messageSendUserFile)) {
        $usersText = file_get_contents($messageSendUserFile);
        $users = explode("\n", $usersText);
        $cnt = 0;
        foreach ($users as $user) {
            if ($user == $ip) {
                $cnt++;
            }
        }
        if ($cnt >= 3) {
            responseJson(["message"=>"1日3回までしか登録できません"], 400);
            return;
        }
        if (mb_strlen($text) > 2000) {
            responseJson(["message"=>"2000文字までしか登録できません"], 400);
            return;
        }
    }

    if (file_exists(dataDir(). '/message/message.txt')) {
        $messages = explode('----------end of message', file_get_contents(dataDir(). '/message/message.txt'));
        if (count($messages) > 500) {
            unset($messages[0]);
            file_put_contents(dataDir().'/message/message.txt', implode('----------end of message', $messages));
        }
    }

    file_put_contents(dataDir().'/message/message.txt', $ip . ' ' . date("Y/m/d H:i:s") . "\n" . $text . "\n----------end of message\n", FILE_APPEND | LOCK_EX);
    file_put_contents($messageSendUserFile, $ip . "\n", FILE_APPEND | LOCK_EX);

    if (mb_strlen($text) > 100) {
        $text = mb_substr($text, 0, 100) . '....';
    }
    lineNotify($text);
    responseJson();
});
route('GET','/robots.txt', function () {
    $data = file_get_contents(publicDir() . '/robots.txt');
    echo ltrim($data);
});

route('GET','/image/:name', function ($name) {
    $file = imageDir() . '/' . $name;

    //MIMEタイプの取得
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file);

    header('Content-Type: '.$mime_type);
    readfile($file);
});

route('POST','/upload', function () {
    saveUploadFile("files", dataDir());
    responseJson("", 200);
});
