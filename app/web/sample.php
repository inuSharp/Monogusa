<?php

// topページ
route('GET','/', function () {
    global $TITLE, $META;

    $description .= ' - ' . C('SITE_TITLE');
    $TITLE        = $description;
    $META = '<meta name="description" content="'.$description.'" />';

    render('top', [
        'result'   => mainContents(),
        'tag_list' => tagList(),
    ]);
});

// 
route('GET','/work/:id', function ($id) {
    global $TITLE, $META;

});

