<?php

// 第3引数は認証するかしないか。true or 省略ならログインが必要
RtGET('/', 'IndexController::view', false);
RtGET('/index', 'IndexController::view', false);


RtGET('/login', 'UserController::loginView', false);
RtPOST('/api/login', 'UserController::login', false);

RtGET('/calendar/events', 'CalendarController::get', false);


// その他画面のみ
RtGET('/:name', 'PageController::index', false);
