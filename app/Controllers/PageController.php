<?php
class PageController
{
    public function index($name)
    {
        global $consts;
        $name = $name == '' ? 'index' : $name;
        siteTitle($name);

        Utils::test();

        return render($name, []);
    }
}
