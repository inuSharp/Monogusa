<?php

$TITLE = 'MONOGUSA';
$STYLE = <<<STYLE
.grid {
  max-width: 1200px;
}
.grid-pad {
  padding-left: 4px;
  padding-right: 4px;
}
STYLE;
function menu($name)
{
    $menuText = file_get_contents(dataDir().'/menu/'.$name.'.txt');
    $menuAry = csvToArray($menuText, false);
    $menu = '';
    foreach ($menuAry as $row) {
        $menu .= '<div class="menu-list"><a href="@(WEB_ROOT)'.$row[1].'">' .$row[0] . '</a></div>';
    }
    return $menu;
}

