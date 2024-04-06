<?php
use carry0987\Sanite\Utils\DBUtil;
use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Models\DataUpdateModel;
use carry0987\Helper\Utils;

class DataUpdate extends DataUpdateModel
{
    public function updateLastlogin(int $user_id, int $time)
    {
        $queryArray = self::getUpdateData('last_login');
        $last_login = array(
            'online_status' => $time+5,
            'last_login' => $time,
            'uid' => $user_id
        );

        return $this->updateSingleData($queryArray, $last_login);
    }

    public function updateUser(int $user_id, array $data)
    {
        $queryArray = self::getUpdateData('user');
        $user = array(
            'username' => $data['username'],
            'password' => $data['password'],
            'group_id' => $data['group_id'],
            'status' => $data['status'],
            'language' => $data['language'],
            'timezone' => $data['timezone'],
            'OTP' => $data['OTP'],
            'uid' => $user_id
        );

        return $this->updateSingleData($queryArray, $user);
    }

    public function updateMultiUser(array $data)
    {
        $user = array();
        $type = 'multi_user';

        foreach ($data as $key => $value) {
            $userItem = ['uid' => $value['uid']];
            if ($value['group_id'] === null) {
                $type = 'user_status';
                $userItem['status'] = $value['status'];
            } else if ($value['status'] === null) {
                $type = 'user_group';
                $userItem['group_id'] = $value['group_id'];
            } else {
                $userItem['group_id'] = $value['group_id'];
                $userItem['status'] = $value['status'];
            }
            $userItem = Utils::orderArray($userItem, ['group_id', 'status', 'uid']);
            $user[$key] = $userItem;
        }
        $queryArray = self::getUpdateData($type);

        return $this->updateMultipleData($queryArray, $user);
    }

    public function updateUserGroup(int $user_id, int $group_id)
    {
        $queryArray = self::getUpdateData('user_group');
        $user_group = array(
            'group_id' => $group_id,
            'uid' => $user_id
        );

        return $this->updateSingleData($queryArray, $user_group);
    }

    public function updateUserStatus(int $user_id, int $status)
    {
        $queryArray = self::getUpdateData('user_status');
        $user_status = array(
            'status' => $status,
            'uid' => $user_id
        );

        return $this->updateSingleData($queryArray, $user_status);
    }

    public function updateTag(int $tag_id, array $data)
    {
        $queryArray = self::getUpdateData('tag');
        $tag = array(
            'name' => $data['name'],
            'group_id' => $data['group_id'],
            'type' => $data['type'],
            'priority' => $data['priority'],
            'id' => $tag_id
        );

        return $this->updateSingleData($queryArray, $tag);
    }

    public function updateMultipleTag(array $tag_id_list, array $data)
    {
        $queryArray = self::getUpdateData('tags');
        $tag = array();
        foreach ($tag_id_list as $key => $value) {
            $tag[$key] = array(
                'group_id' => $data['group_id'],
                'type' => $data['type'],
                'priority' => $data['priority'],
                'id' => $value['id']
            );
        }

        return $this->updateMultipleData($queryArray, $tag);
    }

    public function updateTagGroup(int $group_id, array $data)
    {
        $queryArray = self::getUpdateData('tag_group');
        $tag_group = array(
            'name' => $data['name'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'id' => $group_id
        );

        return $this->updateSingleData($queryArray, $tag_group);
    }

    private static function getUpdateData(string $param)
    {
        $update = array();
        switch ($param) {
            case 'last_login':
                $update['query'] = 'UPDATE user SET online_status = ?,last_login = ? WHERE uid = ?';
                $update['bind'] = 'iii';
                break;
            case 'user':
                $update['query'] = 'UPDATE user SET username = ?,password = ?,group_id = ?,status = ?,language = ?,timezone = ?,OTP = ? WHERE uid = ?';
                $update['bind'] = 'ssiisssi';
                break;
            case 'multi_user':
                $update['query'] = 'UPDATE user SET group_id = ?,status = ? WHERE uid = ?';
                $update['bind'] = 'iii';
                break;
            case 'user_group':
                $update['query'] = 'UPDATE user SET group_id = ? WHERE uid = ?';
                $update['bind'] = 'ii';
                break;
            case 'user_status':
                $update['query'] = 'UPDATE user SET status = ? WHERE uid = ?';
                $update['bind'] = 'ii';
                break;
            case 'tag':
                $update['query'] = 'UPDATE tag SET name = ?, group_id = ?, type = ?, priority = ? WHERE id = ?';
                $update['bind'] = 'siiii';
                break;
            case 'tags':
                $update['query'] = 'UPDATE tag SET group_id = ?, type = ?, priority = ? WHERE id = ?';
                $update['bind'] = 'iiii';
                break;
            case 'tag_group':
                $update['query'] = 'UPDATE tag_group SET name = ?, description = ?, priority = ? WHERE id = ?';
                $update['bind'] = 'ssii';
                break;
            default:
                self::throwDBError('Unsupported Parameter');
                break;
        }

        return $update;
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
