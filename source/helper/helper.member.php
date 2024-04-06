<?php
namespace carry0987\Helper;

use carry0987\Helper\Utils;
use carry0987\Paginator\Paginator;
use Carbon\Carbon;

class MemberHelper extends Helper
{
    private static $status = array(
        0 => 'normal',
        1 => 'banned',
        2 => 'deleted'
    );

    public function __construct($connect_db = null)
    {
        if ($connect_db) {
            parent::__construct($connect_db);
        }
    }

    public static function getStatusList(int $key = null)
    {
        return self::$status[$key] ?? self::$status;
    }

    public static function fetchNumberOfMember()
    {
        return parent::$dataRead->getUserCount();
    }

    public static function fetchMemberInfo(int $uid)
    {
        if (empty($uid)) return false;

        return parent::$dataRead->getUser($uid);
    }

    public static function fetchMemberList(array $data = null)
    {
        $filter = self::buildFilter($data);
        $result = isset($filter['name']) ? parent::$dataRead->getUserSearch($filter['name']) : parent::$dataRead->getUserList();

        // Filter by group and status
        if (!empty($filter)) {
            $result = array_filter($result, function ($member) use ($filter) {
                if (!is_null($filter['group']) && $member['group_id'] !== $filter['group']) return false;
                if (!is_null($filter['status']) && $member['status'] !== $filter['status']) return false;
                return true;
            });
        }

        // Process members
        foreach ($result as &$member) {
            self::formatDate($member);
            self::addStatusParam($member);
        }
        unset($member);

        // Sort and pagination
        if (isset($data['order'])) {
            $result = Utils::sortData($result, $data['order'], $data['sort_by'] ?? 'uid');
        }
        $paginator = new Paginator($result, self::getParam('pageSize'), self::getParam('pageIndex'));

        return [
            'data' => ($paginator->getResult() !== false) ? $paginator->getResult() : [],
            'list' => $paginator->getResult(),
            'total' => $paginator->getTotalItem()
        ];
    }

    public static function getAdminList(int $level = 3)
    {
        $result = self::fetchMemberList();
        $result = array_filter($result, function ($value) use ($level) {
            return $value['level'] >= $level;
        });

        return $result;
    }

    public static function searchMember(array $data)
    {
        if (empty($data['keyword'])) return false;
        $result = parent::$dataRead->getUserSearch();
        if (isset($data['order'])) {
            $result = Utils::sortData($result, $data['order'], $data['sort_by'] ?? 'uid');
        }

        return $result;
    }

    public static function createMember(array $data)
    {
        if (empty($data['data']) || empty($data['password'])) return false;
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        $data = $data['data'];
        $data['username'] = self::checkUsername($data['username']);
        if ($data['username'] === false) return false;
        if (empty($data['username'])) return false;
        $data['password'] = $password;
        $data['language'] = Utils::inputFilter($data['language'] ?? 'en_US');
        $data['status'] = (int) ($data['status'] ?? 0);
        $data['group_id'] = (int) ($data['group_id'] ?? 4);
        $data['join_date'] = $data['last_login'] = $data['online_status'] = time();

        return parent::$dataCreate->createUser($data);
    }

    public static function updateMember(mixed $data, int $login_uid)
    {
        if (empty($data)) return false;
        // Update multiple user
        if (!empty($data['id_list']) && is_array($data['id_list'])) {
            $update_data = array();
            foreach ($data['id_list'] as $key => $value) {
                $update_data[$key] = array(
                    'group_id' => (!empty($data['group_id']) ? (int) $data['group_id'] : null),
                    'status' => (Utils::validateInteger($data['status']) ? (int) $data['status'] : null),
                    'uid' => (int) $value
                );
            }
            $update_data = array_filter($update_data, function ($value) use ($login_uid) {
                return $value['uid'] !== 1 && $value['uid'] !== $login_uid;
            });
            if (empty($update_data)) return false;

            return parent::$dataUpdate->updateMultiUser($update_data);
        }

        // Update single user
        if (empty($data['uid']) || !ctype_digit(strval($data['uid']))) return false;
        $user_data = self::fetchMemberInfo((int) $data['uid']);
        $security_user_data = parent::$dataRead->getUserLogin($user_data['username']);
        if (empty($user_data) || empty($security_user_data['password'])) return false;
        $update_data = array(
            'username' => self::checkUsername($data['username'] ?? $user_data['username']),
            'password' => isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : $security_user_data['password'],
            'group_id' => (int) ($data['group_id'] ?? $user_data['group_id']),
            'status' => (int) ($data['status'] ?? $user_data['status']),
            'language' => Utils::inputFilter($data['language'] ?? $user_data['language']),
            'timezone' => Utils::inputFilter($data['timezone'] ?? $user_data['timezone']),
            'OTP' => Utils::inputFilter($data['OTP'] ?? $user_data['OTP']),
            'uid' => (int) $data['uid']
        );

        return parent::$dataUpdate->updateUser($update_data['uid'], $update_data);
    }

    public static function deleteMember(array $data, int $login_uid)
    {
        if (empty($data['id_list']) || !is_array($data['id_list'])) return false;
        foreach ($data['id_list'] as $key => $value) {
            $data['id_list'][$key] = array('uid' => (int) $value);
        }
        $data['id_list'] = array_filter($data['id_list'], function ($value) use ($login_uid) {
            return $value['uid'] !== 1 && $value['uid'] !== $login_uid;
        });
        if (empty($data['id_list'])) return false;

        return parent::$dataDelete->deleteUser($data['id_list']);
    }

    private static function checkUsername(string $username)
    {
        if (empty($username)) return false;
        $username = Utils::inputFilter($username);
        $username = str_replace(' ', '_', $username);
        $username = preg_replace('/[^0-9A-Za-z_]/', '', $username);
        if (empty($username)) return false;

        return $username;
    }

    private static function buildFilter(array $data): array
    {
        if (empty($data) || empty($data['filter'])) return [];
        $filter = $data['filter'];
        $filter['name'] = Utils::inputFilter($filter['name'] ?? '');
        $filter['group'] = isset($filter['group']) ? (int) $filter['group'] : null;
        $filter['status'] = isset($filter['status']) ? (int) $filter['status'] : null;

        return $filter;
    }

    private static function formatDate(array &$member)
    {
        $member['join_date'] = Carbon::createFromTimestamp($member['join_date'])->toDateTimeString();
        $member['last_login'] = Carbon::createFromTimestamp($member['last_login'])->toDateTimeString();
    }
    
    private static function addStatusParam(array &$member)
    {
        $member['status_param'] = self::getStatusList($member['status']);
    }
}
