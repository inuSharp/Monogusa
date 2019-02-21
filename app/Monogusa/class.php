<?php
class Log
{
    public static $logDir = '';
    public static $rotated  = false;
    public static function getFilePath()
    {
        if (self::$logDir == '') {
            self::$logDir = 'storage/log';
        }
        if (!file_exists(self::$logDir)) {
            echo self::$logDir . "\n";
            if(mkdir(self::$logDir, 0777)){
                //作成したディレクトリのパーミッションを確実に変更
                chmod(self::$logDir, 0777);
            }
        }
        return self::$logDir . '/' . date('Y-m-d') . '.log';
    }
    public static function access($s)
    {
        self::write('ACCESS', $s, self::getFilePath());
    }
    public static function info($s)
    {
        self::write('INFO', $s, self::getFilePath());
    }
    public static function error($s)
    {
        self::write('ERROR', $s, self::getFilePath());
    }
    public static function debug($s)
    {
        if (defined('DEBUG') && DEBUG == true) {
            self::write('DEBUG', $s, self::getFilePath());
        }
    }
    public static function write($tag, $s, $path)
    {
        self::rotate();
        if (is_array($s) || is_object($s)) {
            $s = json_encode($s);
        }
        $s = '[' . date('Y-m-d_H:i:s') . ']' . ' ' . $s;
        file_put_contents($path, $tag . ' : ' . $s . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    private static function rotate()
    {
        // 1リクエストで1回
        if (self::$rotated) {
            return;
        }
        self::$rotated = true;

        // 0:00:00～0:01:00の間はローテートしない
        $nowTime = date('His');
        $time = intval(date('His'));
        if ($time < 100) {
            return;
        }

        foreach(glob(self::$logDir.'/*.log') as $file){
            $logdate = str_replace(['.log','-'], '', basename($file));
            // 今日のログならしない
            if ($logdate == date('Ymd')) {
                continue;
            }
            try {
                $ym      = substr($logdate,0,6);
                $backUpDir = self::$logDir.'/'.$ym;
                if (!file_exists($backUpDir)) {
                    if(mkdir($backUpDir, 0777)){
                        chmod($backUpDir, 0777);
                    }
                }
                rename($file, $backUpDir . '/' . basename($file));
            } catch(\Exception $e) {
                file_put_contents('storage/log_error.txt', 'rotate error');
            }
        }
    }
}

use Illuminate\Database\Capsule\Manager;
class DB extends Manager {}

use Illuminate\Database\Eloquent\Model as EModel;
class Model extends EModel {}

class Commnad {
    public function __destruct()
    {
        Log::info('Command destructor');
    }
}

