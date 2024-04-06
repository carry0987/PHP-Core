<?php
namespace System;

use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Models\DataReadModel;
use carry0987\Sanite\Sanite;

class Check extends DataReadModel
{
    public function __construct(Sanite $sanite)
    {
        parent::__construct($sanite);
    }

    public function checkUserLoginByID(int $uid)
    {
        $queryArray = self::getCheckData('user_login_by_id');
        $dataArray['param'][] = $uid;
        $result = parent::getSingleData($queryArray, $dataArray);

        return isset($result['uid']) ? $result : false;
    }

    //Check user login status on Core loaded
    public function checkUserLoginByName(string $username)
    {
        $queryArray = self::getCheckData('user_login_by_name');
        $dataArray['param'][] = $username;
        $result = parent::getSingleData($queryArray, $dataArray);

        return isset($result['uid']) ? $result : false;
    }

    public function checkUsername(string $username)
    {
        $queryArray = self::getCheckData('user_count_by_name');
        $dataArray['param'][] = $username;
        $result = parent::getDataCount($queryArray, $dataArray);

        return $result === 0 ? true : false;
    }

    public function checkTableExist(string $table_name)
    {
        $result = false;
        if (!empty($table_name)) {
            try {
                $check = $this->connectdb->prepare('SHOW TABLES LIKE ?');
                $check->execute(array($table_name));
                $result = ($check->rowCount() > 0) ? true : $result;
            } catch (\PDOException $e) {
                self::throwDBError($e->getMessage(), $e->getCode());
            }
        }

        return $result;
    }

    private static function getCheckData(string $param)
    {
        $read = array();
        switch ($param) {
            case 'user_login_by_id':
                $read['query'] = 'SELECT u.uid,u.username,u.group_id,u.status,u.language,u.timezone,
                    gl.level AS user_level
                    FROM user AS u
                    LEFT JOIN group_list AS gl ON gl.id = group_id
                    WHERE uid = ?';
                $read['bind'] = 'i';
                break;
            case 'user_login_by_name':
                $read['query'] = 'SELECT u.uid,u.username,u.group_id,u.status,u.language,u.timezone,
                    gl.level AS user_level
                    FROM user AS u
                    LEFT JOIN group_list AS gl ON gl.id = group_id
                    WHERE username = ?';
                $read['bind'] = 's';
                break;
            case 'user_count_by_name':
                $read['query'] = 'SELECT COUNT(username) FROM user WHERE username = ?';
                $read['bind'] = 's';
                break;
            default:
                self::throwDBError('Unsupported Parameter');
                break;
        }

        return $read;
    }

    //Throw database error excetpion
    private static function throwDBError(string $message, mixed $code = null)
    {
        $error = '<h1>Service unavailable</h1>'."\n";
        $error .= '<h2>Error Info :'.$message.'</h2>'."\n";
        if ($code !== null) {
            $error .= '<h3>Error Code :'.$code.'</h3>'."\n";
        }

        throw new DatabaseException($error);
    }
}
