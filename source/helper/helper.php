<?php
namespace carry0987\Helper;

class Helper
{
    protected $connect_db;
    protected static $dataRead;
    protected static $dataUpdate;
    protected static $dataCreate;
    protected static $dataDelete;
    protected static $system = [];
    protected static $param = [];

    const DB_READ = 'db.read';
    const DB_UPDATE = 'db.update';
    const DB_CREATE = 'db.create';
    const DB_DELETE = 'db.delete';

    public function __construct($connect_db = null)
    {
        if ($connect_db) {
            $this->connect_db = $connect_db;
        }
    }

    public function connectDB($connect_db)
    {
        $this->connect_db = $connect_db;
    }

    public function setDB($db_type, $db)
    {
        switch ($db_type) {
            case self::DB_READ:
                self::$dataRead = $db;
                break;
            case self::DB_UPDATE:
                self::$dataUpdate = $db;
                break;
            case self::DB_CREATE:
                self::$dataCreate = $db;
                break;
            case self::DB_DELETE:
                self::$dataDelete = $db;
                break;
        }
    }

    public static function getDB($db_type)
    {
        $db = false;
        switch ($db_type) {
            case self::DB_READ:
                $db = self::$dataRead;
                break;
            case self::DB_UPDATE:
                $db = self::$dataUpdate;
                break;
            case self::DB_CREATE:
                $db = self::$dataCreate;
                break;
            case self::DB_DELETE:
                $db = self::$dataDelete;
                break;
        }

        return $db;
    }

    public static function setSystem(array $value)
    {
        self::$system = $value;
    }

    public static function getSystem(?string $key = null)
    {
        return $key ? self::$system[$key] : self::$system;
    }

    public static function setParam(string|array $key, mixed $value = null)
    {
        if (is_array($key)) {
            self::$param = array_merge($key, self::$param);
            return;
        }

        self::$param[$key] = $value;
    }

    public static function getParam(string $key = null)
    {
        return $key ? self::$param[$key] : self::$param;
    }
}
