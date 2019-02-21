<?php

class Sample3Command extends Commnad
{
    public function run()
    {
        Log::info(__FILE__ . EXEC_TIME);
    }
}
