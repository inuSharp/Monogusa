<?php

// php run test aaa bbb ccc
function test($commandParam)
{
    var_dump($commandParam);
}
function register($commandParam)
{
    $db = new DB();
    $db->connect('localhost', 3307, 'movieSt3db', 'movieHn5user', 'movieUr1pass');
    $filePath = dataDir() . '/rottenEggs/' . $commandParam[0] . '/' . $commandParam[1] . '.txt';
    if (!file_exists($filePath)) {
        throw new Exception("file not found");
    }

    $fileText = mb_convert_encoding(file_get_contents($filePath), 'UTF-8', 'auto');

    $list = explode("\n", $fileText);
    foreach ($list as $row) {
        if (mb_substr($row, 0, 1) == '#') {
            continue;
        }
        if ($row == '') {
            continue;
        }
        
        $cols = explode(',', $row);

        if ($commandParam[0] == 'new') {
            newWork($db, $cols);
        } else if ($commandParam[0] == 'updateTag') {
            updateTag($db, $cols);
        } else {
            //updateWork($db, $cols);
        }
    }
}
function restore($commandParam)
{
    // tags
    $tagFilePath = dataDir() . '/rottenEggs/backup/tags.txt';
    if (!file_exists($tagFilePath)) {
        throw new Exception("file not found");
    }

    // work
    $workFilePath = dataDir() . '/rottenEggs/backup/works.txt';
    if (!file_exists($workFilePath)) {
        throw new Exception("file not found");
    }

    // tag
    $fileText = mb_convert_encoding(file_get_contents($tagFilePath), 'UTF-8', 'auto');
    \Qb::execSQL('truncate table tags;');
    $ary = csvToArray($fileText, false);
    foreach ($ary as $row) {
        $name = $row[0] == '' ? 'NULL' : "'" . $row[0] . "'";
        $type = $row[1] == '' ? 'NULL' : $row[1];

        \Qb::execSQL('insert into tags (name, type) values ('.
            $name . ',' .
            $type
            .');');
    }

    // work
    $fileText = mb_convert_encoding(file_get_contents($workFilePath), 'UTF-8', 'auto');
    \Qb::execSQL('truncate table works;');
    $ary = csvToArray($fileText, false);
    foreach ($ary as $row) {
        $title             = $row[0] == '' ? 'NULL' : "'" . $row[0] . "'";
        $original_title    = $row[1] == '' ? 'NULL' : "'" . $row[1] . "'";
        $official_web_site = $row[2] == '' ? 'NULL' : "'" . $row[2] . "'";
        $quotation         = $row[3] == '' ? 'NULL' : "'" . $row[3] . "'";
        $quote_source      = $row[4] == '' ? 'NULL' : "'" . $row[4] . "'";
        $explanation       = $row[5] == '' ? 'NULL' : "'" . $row[5] . "'";
        $released_at       = $row[6] == '' ? 'NULL' : "'" . $row[6] . "'";
        $point             = $row[7] == '' ? 'NULL' : "'" . $row[7] . "'";
        $special_tag       = $row[8] == '' ? 'NULL' : "'" . $row[8] . "'";
        $tag               = $row[9] == '' ? 'NULL' : "'" . $row[9] . "'";
        $tagOrigin         = $row[9] == '' ? ''     : $row[9];
        $updated_at        = $row[10] == '' ? 'NULL' : "'" . $row[10] . "'";

        \Qb::execSQL('insert into works (
            title             ,
            original_title    ,
            official_web_site ,
            quotation         ,
            quote_source      ,
            explanation       ,
            released_at       ,
            point             ,
            special_tag       ,
            tag               ,
            updated_at
        ) values ('.
            $title.','.
            $original_title.','.
            $official_web_site.','.
            $quotation.','.
            $quote_source.','.
            $explanation.','.
            $released_at.','.
            $point.','.
            $special_tag.','.
            $tag.','.
            $updated_at.');');

        $workId = \Qb::select('select max(id) as id from works;')[0]['id'];

        // work_tagsを追加
        $tags = explode('@c@', $tagOrigin);
        foreach ($tags as $tag1) {
            $tagInfo = \Qb::select("select id from tags where name = '".$tag1."'");
            if (!array_key_exists(0, $tagInfo)) {
                echo 'tag not found : ' . $tag1 . "\n";
                return;
            }
            $tagId = \Qb::select("select id from tags where name = '".$tag1."'")[0]['id'];
            \Qb::execSQL("insert into work_tags (work_id, tag_id) values ('" . $workId . "', '".$tagId."'); ");
        }
    }
}
function newWork($db, $cols)
{
    $result = $db->select("select id from works where title = '".$cols[0]."' and released_at = " . $cols[1]);
    if (count($result) >= 1) {
        echo "work not found. work:" . $cols[0] . "\n";
        return;
    }
    $now = date("Y-m-d H:i:s");
    if ($cols[2] == '') {
        $point = 'NULL';
    } else {
        $point = ceil($cols[2] * 10) + mt_rand(0, 5);
    }
    $db->execSQL("insert into works (title,released_at,point,updated_at) values ('". $cols[0]."', ".$cols[1].", ".$point.", '".$now."');");
}
// keywork.txtを登録する
function updateTagList($commandParam)
{
    $db = new DB();
    $db->connect('localhost', 3307, 'movieSt3db', 'movieHn5user', 'movieUr1pass');
    $db->transactionStart();
    try {
        $list = csvToArray(file_get_contents(dataDir() . '/rottenEggs/keyword.txt'), false);
        foreach ($list as $cols) {
            $result = $db->select("select count(*) as cnt from tags where name = '".$cols[0]."';");
            if ($result[0]['cnt'] == 0) {
                $db->execSQL("insert into tags (name,type) values (':name', :type) ;", ['name'=>$cols[0], 'type' => $cols[1]]);
            }
        }

        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}
function updateTag($db, $cols)
{
    $db->transactionStart();
    try {
        $result = $db->select("select id from works where title = '".$cols[0]."' and released_at = " . $cols[1]);

        if (count($result) == 0) {
            echo "work not found. work:" . $cols[0] . "\n";
            return;
        }
        $work_id = $result[0]['id'];

        // work.tagを更新
        $tagText = $cols[3];
        if ($tagText == '') {
            $db->execSQL("update works set tag = NULL where id = " . $work_id);
        } else {
            $db->execSQL("update works set tag = '" .$tagText. "' where id = " . $work_id);
        }
        // work_tagsを削除
        $db->execSQL("delete from work_tags where work_id = " . $work_id . ";");

        if ($tagText == '') {
            return;
        }
        // work_tagsを追加
        $tags = explode('@c@', $tagText);
        foreach ($tags as $tag) {
            // tagsからtag_idを取得(なければ作成)
            $tagInfo = $db->select("select id from tags where name = '".$tag."'");
            if (count($tagInfo) == 0) {
                $db->execSQL("insert into tags (name) values ('".$tag."');");
                $tagInfo = $db->select("select id from tags where name = '".$tag."'");
            }
            $db->execSQL("insert into work_tags (work_id, tag_id) values ('" . $work_id . "', '".$tagInfo[0]['id']."'); ");
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function calcListToCsv($commandParam)
{
    $filePath = dataDir() . '/rottenEggs/worksOfCalc.txt';
    if (!file_exists($filePath)) {
        throw new Exception("file not found");
    }

    $fileText = mb_convert_encoding(file_get_contents($filePath), 'UTF-8', 'auto');

    $list = explode("\n", $fileText);
    $date = '';
    foreach ($list as $row) {
        if (mb_substr($row, 0, 1) == '#') {
            continue;
        }
        if ($row == '') {
            continue;
        }
        $cols = explode(',', $row);

        $tag = '';
        for($i = 3; $i < count($cols); $i++) {
            if ($cols[$i] == '') {
                continue;
            }
            if ($tag != '') {
                $tag .= '@c@';
            }
            $tag .= $cols[$i];
        }

        $date .= $cols[0] . ',' . $cols[1] . ',' . $cols[2] . ',' . $tag . "\n";
    }

    file_put_contents(dataDir(). '/rottenEggs/out.txt', $date);
}
function backup($commandParam)
{
    // works
    $sql =<<<SQL
        select 
            `title`          ,
            `original_title` ,
            `official_web_site` ,
            `quotation`      ,
            `quote_source`   ,
            `explanation`    ,
            `released_at`    ,
            `point`          ,
            `special_tag`    ,
            `tag`            ,
            `updated_at`
        from 
            works;
SQL;

    $works = \Qb::select($sql);
    file_put_contents(dataDir(). '/rottenEggs/backup/works.txt', arrayToCsv($works));

    $tags = \Qb::select('select name, type from tags;');
    file_put_contents(dataDir(). '/rottenEggs/backup/tags.txt', arrayToCsv($tags));
}
function humancheck_gen()
{
    //えーびーしーいーえふあいじぇいけいえるえむえぬおーぴーあーるえすゆーえっくすわい
    $data = [
        ['caption' =>'えー', 'value'=>'a'],
        ['caption' =>'びー', 'value'=>'b'],
        ['caption' =>'しー', 'value'=>'c'],
        ['caption' =>'いー', 'value'=>'e'],
        ['caption' =>'えふ', 'value'=>'f'],
        ['caption' =>'あい', 'value'=>'i'],
        ['caption' =>'けい', 'value'=>'k'],
        ['caption' =>'える', 'value'=>'l'],
        ['caption' =>'えむ', 'value'=>'m'],
        ['caption' =>'えぬ', 'value'=>'n'],
        ['caption' =>'おー', 'value'=>'o'],
        ['caption' =>'ぴー', 'value'=>'p'],
        ['caption' =>'あーる', 'value'=>'r'],
        ['caption' =>'えす', 'value'=>'s'],
        ['caption' =>'ゆー', 'value'=>'u'],
        ['caption' =>'えっくす', 'value'=>'x'],
        ['caption' =>'わい', 'value'=>'y'],
    ];

    $list = "";
    $values = [];
    for ($i=0;$i<2000;$i++) {
        $caption = '';
        $value   = '';
        for ($j=0;$j<=2;$j++) {
            $rnd = $data[mt_rand(0,count($data)-1)];
            $caption .= $rnd['caption'];
            $value   .= $rnd['value'];
        }
        if ($value == 'sex') {
            continue;
        }
        if (in_array($value, $values)) {
            continue;
        }
        $values[] = $value;
        $list .= $caption .','. $value . "\n";
    }
    file_put_contents('human_check.txt', $list);

}
function testmail()
{
    sendMail("inusharp@gmail.com", "ご無沙汰しております", "おひさしぶりです\nまたお食事にでも行きましょう。", "register@rotteneggs.jp");
}
