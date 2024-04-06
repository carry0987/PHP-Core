<?php
use carry0987\Helper\Utils;

if (defined('IN_ADMIN') !== true) {
    exit('Access Denied');
}

function checkURLRewrite()
{
    return in_array('mod_rewrite', apache_get_modules());
}

function generatePassword(int $length, string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces[] = $keyspace[random_int(0, $max)];
    }

    return implode('', $pieces);
}

function checkTypeChange($change_type, $type_array)
{
    $result = $type_array[0];
    foreach ($type_array as $key) {
        if ($key === $change_type) {
            $result = $key;
        }
    }

    return $result;
}

function checkApplyChange($change_apply)
{
    foreach ($change_apply as $key => $value) {
        if ($value === 'enable') {
            $result[] = $key;
        } else {
            $result[] = '';
        }
    }

    return $result;
}

function checkDatabaseSize($connectdb, $db_name): string
{
    $query = $connectdb->prepare("SELECT table_name AS `Table`,
                    round(((data_length + index_length) / 1024 / 1024), 2) 
                    AS `Size` FROM information_schema.TABLES 
                    WHERE table_schema = :dbname");
    $query->bindValue(':dbname', $db_name, PDO::PARAM_STR);
    $query->execute();
    $tables = $query->fetchAll(PDO::FETCH_OBJ);
    $size = 0;
    foreach ($tables as $table) {
        $size += $table->Size;
    }

    return Utils::formatFileSize($size);
}
