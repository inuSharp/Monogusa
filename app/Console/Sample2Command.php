<?php

class Sample2Command extends Commnad
{
    public function run()
    {
        Log::info(__FILE__ . EXEC_TIME);
    }
}
