<?php
use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Models\DataCreateModel;

class DataCreate extends DataCreateModel
{
    public function createUser(array $data)
    {
        $queryArray = self::getCreateData('user');
        $user_data = array(
            'username' => $data['username'],
            'password' => $data['password'],
            'group_id' => $data['group_id'],
            'language' => $data['language'],
            'online_status' => $data['online_status'],
            'last_login' => $data['last_login'],
            'join_date' => $data['join_date']
        );

        return parent::createSingleData($queryArray, $user_data);
    }

    public function createTag(array $data_array)
    {
        $queryArray = self::getCreateData('tag');
        $tag = array();
        foreach ($data_array as $value) {
            $tag[] = array(
                'name' => $value['name'],
                'group_id' => $value['group_id'],
                'type' => $value['type'],
                'priority' => $value['priority']
            );
        }

        return $this->createMultipleData($queryArray, $tag);
    }

    public function createTagGroup(array $data)
    {
        $queryArray = self::getCreateData('tag_group');
        $tag_group = array(
            'name' => $data['name'],
            'description' => $data['description'],
            'priority' => $data['priority']
        );

        return $this->createSingleData($queryArray, $tag_group);
    }

    private static function getCreateData(string $param)
    {
        $create = array();
        switch ($param) {
            case 'user':
                $create['query'] = 'INSERT INTO user (
                    username,password,group_id,
                    language,online_status,last_login,join_date
                ) VALUES (?,?,?,?,?,?,?)';
                $create['bind'] = 'ssisiii';
                break;
            case 'tag':
                $create['query'] = 'INSERT INTO tag (
                    name,group_id,type,priority
                ) VALUES (?,?,?,?)';
                $create['bind'] = 'siii';
                break;
            case 'tag_group':
                $create['query'] = 'INSERT INTO tag_group (
                    name,description,priority
                ) VALUES (?,?,?)';
                $create['bind'] = 'ssi';
                break;
            default:
                self::throwDBError('Unsupported Parameter');
                break;
        }

        return $create;
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
