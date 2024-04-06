<?php
use carry0987\Sanite\Exceptions\DatabaseException;
use carry0987\Sanite\Models\DataDeleteModel;

class DataDelete extends DataDeleteModel
{
    public function deleteUser(array|int $id_list)
    {
        $queryArray = self::getDeleteData('user');
        if (!is_array($id_list)) {
            $tmp = array();
            $tmp[] = array('uid' => $id_list);
            $id_list = $tmp;
        }
        $delete['list'] = $id_list;

        return parent::deleteMultipleData($queryArray, $delete);
    }

    public function deleteTag(array $tag_id_list)
    {
        $queryArray = self::getDeleteData('tag');
        $delete['list'] = $tag_id_list;

        return parent::deleteMultipleData($queryArray, $delete);
    }

    public function deleteTagGroup(array $group_id_list)
    {
        $queryArray = self::getDeleteData('tag_group');
        $delete['list'] = $group_id_list;

        return parent::deleteMultipleData($queryArray, $delete);
    }

    private static function getDeleteData(string $param)
    {
        $delete = array();
        switch ($param) {
            case 'user':
                $delete['query'] = 'DELETE FROM user WHERE uid = ?';
                $delete['bind'] = 'i';
                break;
            case 'tag':
                $delete['query'] = 'DELETE FROM tag WHERE id = ?';
                $delete['bind'] = 'i';
                break;
            case 'tag_group':
                $delete['query'] = 'DELETE FROM tag_group WHERE id = ?';
                $delete['bind'] = 'i';
                break;
            default:
                self::throwDBError('Unsupported Parameter');
                break;
        }

        return $delete;
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
