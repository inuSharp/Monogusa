<?php

route('GET','/logout', function () {
    setSession('loginFlg', false);
    setcookie("7CXziwo".setting('app_key')."cb9", '', time(), '/');
    // remember_tokensは残したままでOK
    redirect('/');
});

route('GET','/login', function () {
    if (LOGIN_FLG) {
        redirect('/');
    } else {
        render('login', ['API_SERVER'=>'localhost']);
    }
});

route('POST','/api/login', function () {
    $email    = request('login_id');
    $password = request('login_pass');
    if (is_null($email) || is_null($password)) {
        responseJson(['message'=> '入力値が不正です'], 400);
        return;
    }

    $users = \Qb::select(SQL('GetUserByEmail'), ['email' => $email]);
    if (count($users) == 0) {
        responseJson(['message'=> '登録されていないメールアドレスです。'], 400);
        return;
    }

    if (password_verify($password, $users[0]['password'])) {
        $key = md5(makeRandStr(10) . $users[0]['id'] . str_replace('.', '', clientIp()) . makeRandStr(10));
        \Qb::execSQL("update remember_tokens set token = '" .$key. "' where user_id = " .$users[0]['id']. ";");
        // 7日間有効
        setcookie("7CXziwo".setting('app_key')."cb9", $key, time()+60*60*24*7, '/');
        session_regenerate_id(true);
        setSession('loginFlg', true);
        setSession('_token', makeRandStr());
        responseJson(['url' => WEB_ROOT]);
        return;
    } else {
        responseJson(['message'=> 'パスワードが不正です'], 400);
        return;
    }
});

