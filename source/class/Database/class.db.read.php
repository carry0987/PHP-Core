<?php
use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Models\DataReadModel;

class DataRead extends DataReadModel
{
    public function getUserLogin(string $username)
    {
        $queryArray = self::getReadData('user_login');
        $dataArray['param'][] = $username;

        return parent::getSingleData($queryArray, $dataArray);
    }

    public function getUser(int $data_id)
    {
        $queryArray = self::getReadData('user_info');
        $dataArray['param'][] = $data_id;

        return parent::getSingleData($queryArray, $dataArray);
    }

    public function getUserList()
    {
        $queryArray = self::getReadData('user_list');

        return parent::getMultipleData($queryArray);
    }

    public function getUserCount()
    {
        $queryArray = self::getReadData('user_count');

        return parent::getDataCount($queryArray);
    }

    public function getUserSearch(string $keyword)
    {
        $queryArray = self::getReadData('user_search');
        $dataArray['param'][] = $keyword;

        return parent::getMultipleData($queryArray, $dataArray);
    }

    public function getGroup(int $data_id)
    {
        $queryArray = self::getReadData('group_info');
        $dataArray['param'][] = (int) $data_id;

        return parent::getSingleData($queryArray, $dataArray);
    }

    public function getGroupList()
    {
        $queryArray = self::getReadData('group_list');

        return parent::getMultipleData($queryArray);
    }

    public function getAreaData()
    {
        $queryArray = self::getReadData('area_data');

        return parent::getMultipleData($queryArray);
    }

    public function getTagByID(int $tag_id)
    {
        if (empty($tag_id)) return false;
        $queryArray = self::getReadData('tag_by_id');
        $dataArray['param'][] = $tag_id;

        return parent::getSingleData($queryArray, $dataArray);
    }

    public function getTagByName(string $tag_name, int $tag_type = null)
    {
        $queryArray = self::getReadData('tag_by_name');
        $dataArray['param'][] = $tag_name;
        if ($tag_type !== null) {
            $queryArray = self::getReadData('tag_by_name_and_type');
            $dataArray['param'] = array($tag_name, $tag_type);
        }

        return parent::getMultipleData($queryArray, $dataArray);
    }

    public function getTagCount(int $group_id = null)
    {
        $queryArray = self::getReadData($group_id === null ? 'tag_count' : 'tag_count_by_group');
        $read['query'] = $queryArray['query'];
        if ($group_id !== null) {
            $read['bind'] = $queryArray['bind'];
            $dataArray['param'][] = $group_id;
        }

        return parent::getDataCount($read);
    }

    public function getTagList(int $group_id = null)
    {
        $queryArray = self::getReadData($group_id === null ? 'tag_list' : 'tag_by_group');
        $dataArray = null;
        if ($group_id !== null) {
            $dataArray['param'][] = $group_id;
        }

        return parent::getMultipleData($queryArray, $dataArray);
    }

    public function getTagListByFilter(string $tag_name)
    {
        $queryArray = self::getReadData('tag_list_filter');
        $dataArray['param'][] = $tag_name;

        return parent::getMultipleData($queryArray, $dataArray);
    }

    public function getTagGroupList()
    {
        $queryArray = self::getReadData('tag_group_list');

        return parent::getMultipleData($queryArray);
    }

    private static function getReadData(string $param)
    {
        $read = array();
        switch ($param) {
            case 'user_login':
                $read['query'] = 'SELECT uid,username,password,OTP FROM user WHERE username = ? LIMIT 1';
                $read['bind'] = 's';
                break;
            case 'user_info':
                $read['query'] = 'SELECT u.username,u.group_id,u.status,u.language,u.timezone,u.online_status,u.last_login,u.join_date,u.OTP,
                    ug.level,ug.param AS group_param,
                    social_tg.tg_id,social_tg.first_name,social_tg.last_name,social_tg.tg_username,
                    social_tg.tg_profile,social_tg.auth_date,social_tg.added,social_tg.updated,
                    social_line.line_id,social_line.line_display_name,social_line.line_profile,
                    social_line.line_access_token,social_line.line_expires_in,social_line.line_added,social_line.line_updated
                    FROM user AS u
                    LEFT JOIN group_list AS ug ON ug.id = group_id
                    LEFT JOIN social_tg_login AS social_tg ON social_tg.user_id = u.uid
                    LEFT JOIN social_line_login AS social_line ON social_line.user_id = u.uid
                    WHERE uid = ? LIMIT 1';
                $read['bind'] = 'i';
                break;
            case 'user_list':
                $read['query'] = 'SELECT u.uid,u.username,u.group_id,u.status,u.language,u.timezone,u.online_status,u.last_login,u.join_date,u.OTP,
                    ug.level,ug.param AS group_param,
                    social_tg.tg_id,social_tg.first_name,social_tg.last_name,social_tg.tg_username,
                    social_tg.tg_profile,social_tg.auth_date,social_tg.added,social_tg.updated,
                    social_line.line_id,social_line.line_display_name,social_line.line_profile,
                    social_line.line_access_token,social_line.line_expires_in,social_line.line_added,social_line.line_updated
                    FROM user AS u
                    LEFT JOIN group_list AS ug ON ug.id = group_id
                    LEFT JOIN social_tg_login AS social_tg ON social_tg.user_id = u.uid
                    LEFT JOIN social_line_login AS social_line ON social_line.user_id = u.uid
                    ORDER BY uid ASC';
                $read['bind'] = null;
                break;
            case 'user_count':
                $read['query'] = 'SELECT COUNT(uid) FROM user';
                break;
            case 'user_search':
                $read['query'] = 'SELECT u.uid,u.username,u.group_id,u.status,u.language,u.timezone,u.online_status,u.last_login,u.join_date,u.OTP,
                    ug.level,ug.param AS group_param,
                    social_tg.tg_id,social_tg.first_name,social_tg.last_name,social_tg.tg_username,
                    social_tg.tg_profile,social_tg.auth_date,social_tg.added,social_tg.updated,
                    social_line.line_id,social_line.line_display_name,social_line.line_profile,
                    social_line.line_access_token,social_line.line_expires_in,social_line.line_added,social_line.line_updated
                    FROM user AS u
                    LEFT JOIN group_list AS ug ON ug.id = group_id
                    LEFT JOIN social_tg_login AS social_tg ON social_tg.user_id = u.uid
                    LEFT JOIN social_line_login AS social_line ON social_line.user_id = u.uid
                    WHERE u.username LIKE CONCAT(\'%\',?,\'%\') ORDER BY uid ASC';
                $read['bind'] = 's';
                break;
            case 'group_info':
                $read['query'] = 'SELECT id,param,level FROM group_list WHERE id = ? LIMIT 1';
                $read['bind'] = 'i';
                break;
            case 'group_list':
                $read['query'] = 'SELECT id,param,level FROM group_list ORDER BY id ASC';
                $read['bind'] = null;
                break;
            case 'tag_list':
                $read['query'] = 'SELECT t.id,t.name,t.group_id,t.type,t.priority,
                    tg.name AS group_name
                    FROM tag AS t LEFT JOIN tag_group AS tg ON t.group_id = tg.id';
                $read['bind'] = null;
                break;
            case 'tag_list_filter':
                $read['query'] = 'SELECT t.id,t.name,t.group_id,t.type,t.priority,
                    tg.name AS group_name
                    FROM tag AS t
                    LEFT JOIN tag_group AS tg ON t.group_id = tg.id
                    WHERE t.name LIKE CONCAT(\'%\',?,\'%\')';
                $read['bind'] = 's';
                break;
            case 'tag_group_list':
                $read['query'] = 'SELECT id,name,description,priority FROM tag_group';
                $read['bind'] = null;
                break;
            case 'tag_by_id':
                $read['query'] = 'SELECT id,name,group_id,type,priority FROM tag WHERE id = ?';
                $read['bind'] = 'i';
                break;
            case 'tag_by_name':
                $read['query'] = 'SELECT id,name,group_id,type,priority FROM tag WHERE (name REGEXP ?) ORDER BY id ASC';
                $read['bind'] = 's';
                break;
            case 'tag_by_name_and_type':
                $read['query'] = 'SELECT id,name,group_id,type,priority FROM tag WHERE (name REGEXP ? AND type = ?) ORDER BY id ASC';
                $read['bind'] = 'si';
                break;
            case 'tag_by_group':
                $read['query'] = 'SELECT id,name,group_id,type,priority FROM tag WHERE group_id = ? ORDER BY id ASC';
                $read['bind'] = 'i';
                break;
            case 'tag_count':
                $read['query'] = 'SELECT COUNT(id) FROM tag';
                break;
            case 'tag_count_by_group':
                $read['query'] = 'SELECT COUNT(id) FROM tag WHERE group_id = ?';
                $read['bind'] = 'i';
                break;
            case 'area_data':
                $read['query'] = 'SELECT id,zipcode,city,area FROM area_data';
                $read['bind'] = null;
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
