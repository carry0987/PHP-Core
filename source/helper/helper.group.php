<?php
namespace carry0987\Helper;

class GroupHelper extends Helper
{
    public function __construct($connect_db = null)
    {
        if ($connect_db) {
            parent::__construct($connect_db);
        }
    }

    public static function fetchNumberOfGroup()
    {
        $list = self::fetchGroupList();

        return count($list);
    }

    public static function fetchGroupList()
    {
        return parent::$dataRead->getGroupList();
    }

    public static function fetchGroupLevel(array $data)
    {
        if (empty($data['id'])) return false;
        $result = parent::$dataRead->getGroupInfo($data['id']);

        return $result['level'] ?? false;
    }
}
