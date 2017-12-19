<?php

// topページ
route('GET','/', function () {
    global $TITLE, $META;
    $description = 'Top - ' . C('SITE_TITLE');
    $TITLE        = $description;
    $META = '<meta name="description" content="'.$description.'" />';

    render('top', [
        'result'   => '',
        'expression' => false,
    ]);
});

// 
route('GET','/list/:id', function ($id) {
    global $TITLE, $META;

    render('sample', [
        'paramName'   => $id,
    ]);
});

