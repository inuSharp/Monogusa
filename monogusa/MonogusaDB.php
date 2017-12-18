<?php
// DB
class DB
{
    private $dbh;
    public $sqls;

    public function connect($host, $port, $dbname, $user, $pass)
    {
        $this->dbh = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$dbname, $user, $pass,
            [
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET CHARACTER SET `utf8`",
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => true
            ]
        );

        // xml関数はコメントがあると正しく動かない。コメントを削除してキャッシュを作成
        $makeCache = false;
        if (!file_exists(dataDir() .'/cache/sql.xml')) {
            $makeCache = true;
        } else if (filemtime(dataDir() .'/cache/sql.xml' ) < filemtime(dataDir() .'/sql.xml' )) {
            $makeCache = true;
        }

        if ($makeCache) {
            $sqlText = file_get_contents(dataDir() .'/sql.xml' );
            if (preg_match_all("/<!--.*?-->/", $sqlText, $match)) {
                $hits = $match[0];
                foreach ($hits as $before) {
                    $sqlText  = str_replace($before, '', $sqlText);
                }
                $sqlText = preg_replace('/^\n/m', '',$sqlText);
            }
            file_put_contents(dataDir() .'/cache/sql.xml', $sqlText);
        }

        $this->sqls = xml(dataDir() .'/cache/sql.xml');
        foreach ($this->sqls as &$row) {
            $row = ltrim($row, "\n");
        }
    }

    public function bind($sql,$bef,$aft)
    {
        return str_replace($bef,$aft,$sql);
    }

    public function execSQL($sql, $data = null)
    {
        if (!is_null($data)) {
            $sql = bind($data, $sql);
        }
        $stmt = $this->dbh->prepare($sql);
        try {
            $stmt->execute();
        } catch (\Exception $e) {
            \Log::error(trim($sql));
            throw $e;
        }
    }

    public function select($sql, $data = null)
    {
        if (!is_null($data)) {
            $sql = bind($data, $sql);
        }
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll();
        $c = [];
        $rowIndex = -1;
        foreach ($rs as $row) {
            $rowIndex++;
            $c[$rowIndex] = [];
            foreach ($row as $colName => $colValue) {
                if (is_numeric($colName)) {
                    continue;
                }
                $c[$rowIndex][$colName] = $colValue;
            }
        }
        return $c;
    }

    public function deleteAndResetIncremnt($table)
    {
        if (is_array($table)) {
            foreach ($table as $t) {
                $this->execSQL('delete from ' . $t);
                $this->execSQL('ALTER TABLE '.$t.' AUTO_INCREMENT = 1');
            }
        } else {
            $this->execSQL('delete from ' . $table);
            $this->execSQL('ALTER TABLE '.$table.' AUTO_INCREMENT = 1');
        }
        
    }

    public function transactionStart()
    {
        $this->dbh->beginTransaction();
    }
    public function commit()
    {
        $this->dbh->commit();
    }
    public function rollback()
    {
        $this->dbh->rollBack();
    }

    public function getOneData($sql, $name)
    {
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute();
        while ($rs = $stmt->fetch(PDO::FETCH_OBJ)) {
            return $rs->$name;
        }
    }
}

class Qb {
    public static function select($sql, $data = null)
    {
        $db = getDBConnection();
        return $db->select($sql, $data);
    }
    public static function execSQL($sql, $data = null)
    {
        $db = getDBConnection();
        $db->execSQL($sql, $data);
    }
    public static function commit()
    {
        $db = getDBConnection();
        $db->commit();
    }
    public static function rollback()
    {
        $db = getDBConnection();
        $db->rollback();
    }
}

function SQL($name) {
    $db = getDBConnection();
    return $db->sqls[$name];
}

function getDBConnection() {
    static $db;
    if (is_null($db)) {
        $db = new \DB();
        $db->connect(setting('DB_HOST'), setting('DB_PORT'), setting('DB_NAME'), setting('DB_USER'), setting('DB_PASS'));
    }
    return $db;
}
function DbTransaction($callback) {
    $db = getDBConnection();
    $db->transactionStart();
    try {
        $ret = $callback();
        $db->commit();
    } catch (Exception $ex) {
        $db->rollback();
        throw $ex;
    }
    return $ret;
}

