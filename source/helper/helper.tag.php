<?php
namespace carry0987\Helper;

use carry0987\Tag\Tag;

class TagHelper extends Helper
{
    public function __construct($connect_db = null)
    {
        if ($connect_db) {
            parent::__construct($connect_db);
        }
    }

    public static function fetchNumberOfTag(int $group_id = null)
    {
        return parent::$dataRead->getTagCount($group_id);
    }

    public static function fetchTagList(mixed $filter = null)
    {
        if (!is_array($filter)) {
            $group_id = $filter ? (int) $filter : null;
            $result = parent::$dataRead->getTagList($group_id);
        } else {
            $filter['name'] = Utils::inputFilter($filter['name']) ?? '';
            $result = parent::$dataRead->getTagListByFilter($filter['name']);
            if (!empty($result)) {
                if (!empty($filter['id_list'])) {
                    foreach ($result as $key => $value) {
                        if (!in_array($value['group_id'], $filter['id_list'])) {
                            unset($result[$key]);
                        }
                    }
                }
            }
        }

        return $result ?? array();
    }

    public static function sortTagList(array $list, mixed $order = SORT_ASC)
    {
        if (empty($list)) return array();
        // Sort by id
        $id_list = array();
        foreach ($list as $key => $value) {
            $id_list[$key] = $value['id'];
        }
        $order = (is_string($order) && $order === 'desc') ? SORT_DESC : SORT_ASC;
        array_multisort($id_list, $order, $list);

        return $list;
    }

    public static function fetchGroupList(int $group_id = null)
    {
        $list = parent::$dataRead->getTagGroupList();
        if (empty($group_id)) return $list;

        return $list[$group_id] ?? [];
    }

    public static function createTag(array $data)
    {
        $tag = new Tag();
        $data['name'] = Utils::inputFilter($data['name']);
        if (empty($data['name'])) return false;
        $tag_list = $tag->setString($data['name'])->getList();
        if (empty($tag_list)) return false;
        $data['group_id'] = (int) ($data['group'] ?? 0);
        if (empty($data['group_id'])) return false;
        $data['type'] = 0;
        $data['priority'] = 0;
        $data['name'] = $tag_list[0];
        if (count($tag_list) > 1) {
            $tags = array();
            foreach ($tag_list as $value) {
                $tags[] = array(
                    'name' => $value,
                    'group_id' => $data['group_id'],
                    'type' => $data['type'],
                    'priority' => $data['priority']
                );
            }
            return parent::$dataCreate->createTag($tags);
        }

        return parent::$dataCreate->createTag(array($data));
    }

    public static function createGroup(array $data)
    {
        $data['name'] = Utils::inputFilter($data['name']);
        if (empty($data['name'])) return false;
        $data['description'] = Utils::inputFilter($data['description'] ?? '');
        $data['priority'] = (int) ($data['priority'] ?? 0);

        return parent::$dataCreate->createTagGroup($data);
    }

    public static function updateTag(array $data)
    {
        $data['group_id'] = (int) ($data['group'] ?? 0);
        $data['type'] = (int) ($data['type'] ?? 0);
        $data['priority'] = (int) ($data['priority'] ?? 0);
        // Update tag
        if (!isset($data['id_list'])) {
            $tag_id = (int) ($data['id'] ?? 0);
            if (empty($tag_id)) return false;
            $data['name'] = Utils::inputFilter($data['name']);
            if (empty($data['name'])) return false;
            $result = parent::$dataUpdate->updateTag($tag_id, $data);
        } else {
            if (!is_array($data['id_list'])) return false;
            foreach ($data['id_list'] as $key => $value) {
                $data['id_list'][$key] = array('id' => (int) $value);
            }
            $result = parent::$dataUpdate->updateMultipleTag($data['id_list'], $data);
        }

        return $result;
    }

    public static function updateGroup(array $data)
    {
        $data['name'] = Utils::inputFilter($data['name']);
        if (empty($data['name']) || empty($data['id'])) return false;
        $data['description'] = Utils::inputFilter($data['description'] ?? '');
        $data['priority'] = (int) ($data['priority'] ?? 0);
        $group_id = (int) $data['id'];

        return parent::$dataUpdate->updateTagGroup($group_id, $data);
    }

    public static function deleteTag(array $data)
    {
        if (!is_array($data['id_list'])) return false;
        foreach ($data['id_list'] as $key => $value) {
            $data['id_list'][$key] = array('id' => (int) $value);
        }

        return parent::$dataDelete->deleteTag($data['id_list']);
    }

    public static function deleteGroup(array $data)
    {
        if (!is_array($data['id_list'])) return false;
        foreach ($data['id_list'] as $key => $value) {
            $data['id_list'][$key] = array('id' => (int) $value);
        }

        return parent::$dataDelete->deleteTagGroup($data['id_list']);
    }
}
