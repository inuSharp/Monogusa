<?php

// topページ
route('GET','/', function () {
    render('sample1', ['menuList'=>menu('default')]);
});

// sample2ページ
route('GET','/sample2', function () {
    render('sample2', ['menuList'=>menu('default'),'paramAAA'=>'test']);
});

