<?php
namespace System;

use PDOException;
use Exception;

class Search
{
    private $connectdb = null;
    private $table = array();
    private $search_data = array();

    public function __construct()
    {
    }

    public function setConnection($connectdb)
    {
        $this->connectdb = $connectdb;
    }

    public function setTableName($value)
    {
        $this->table['name'] = $value;
    }

    public function setTableSource($value)
    {
        $this->table['source'] = $value;
    }

    public function truncateIndex()
    {
        $result = false;
        if ($this->connectdb !== null) {
            try {
                $truncate_table = $this->connectdb->exec('TRUNCATE TABLE `'.$this->table['name'].'`');
                $result = ($truncate_table !== false ? true : false);
            } catch (PDOException $e) {
                self::throwDBError($e->getMessage(), $e->getCode());
            }
        }

        return $result;
    }

    public function rebuildIndex()
    {
        $result = false;
        if ($this->connectdb !== null) {
            try {
                $query = 'INSERT `'.$this->table['name'].'` ';
                $query .= 'SELECT id,name ';
                $query .= 'FROM `'.$this->table['source'].'`';
                $rebuild_table = $this->connectdb->exec($query);
                $result = ($rebuild_table !== false ? true : false);
            } catch (PDOException $e) {
                self::throwDBError($e->getMessage(), $e->getCode());
            }
        }

        return $result;
    }

    public function updateIndex(int $id, string $title)
    {
        $result = false;
        if ($this->connectdb !== null) {
            $update['query'] = 'UPDATE '.$this->table['name'].' SET name = ? WHERE id = ?';
            try {
                $update['stmt'] = $this->connectdb->prepare($update['query']);
                $update['stmt']->bindValue(1, $title, \PDO::PARAM_STR);
                $update['stmt']->bindValue(2, $id, \PDO::PARAM_INT);
                $result = $update['stmt']->execute();
            } catch (PDOException $e) {
                self::throwDBError($e->getMessage(), $e->getCode());
            }
        }

        return $result;
    }

    public function insertNewRow($data_array)
    {
        $result = false;
        if ($this->connectdb !== null) {
            $create['query'] = 'INSERT INTO '.$this->table['name'].' (name,id) VALUES (?,?)';
            try {
                $create['stmt'] = $this->connectdb->prepare($create['query']);
                $create['stmt']->bindValue(1, $data_array[0], \PDO::PARAM_STR);
                $create['stmt']->bindValue(2, $data_array[1], \PDO::PARAM_INT);
                $result = $create['stmt']->execute();
            } catch (PDOException $e) {
                self::throwDBError($e->getMessage(), $e->getCode());
            }
        }

        return $result;
    }

    public function deleteRow(int $id)
    {
        $result = false;
        if ($this->connectdb !== null) {
            $delete['query'] = 'DELETE FROM '.$this->table['name'].' WHERE id = ?';
            try {
                $delete['stmt'] = $this->connectdb->prepare($delete['query']);
                $delete['stmt']->bindValue(1, $id, \PDO::PARAM_INT);
                $result = $delete['stmt']->execute();
            } catch (PDOException $e) {
                self::throwDBError($e->getMessage(), $e->getCode());
            }
        }

        return $result;
    }

    public function setSearchString($value)
    {
        $this->search_data['input'] = $value;
    }

    public function sliceKeyword($search_string)
    {
        $keyword = array(
            'include' => array(),
            'exclude' => array()
        );
        $search_string = trim($search_string);
        $keyword['list'] = explode(' ', $search_string);
        if (isset($keyword['list'][0])) {
            foreach ($keyword['list'] as $key => $value) {
                if (function_exists('str_starts_with')) {
                    //Exclude this keyword
                    if (str_starts_with($value, '-')) {
                        $value = ltrim($value, '-');
                        $keyword['exclude'][] = preg_quote($value, '/');
                    } else {
                        $keyword['include'][] = preg_quote($value, '/');
                    }
                    continue;
                }
                if (substr($value, 0, 1) === '-') {
                    $value = ltrim($value, '-');
                    $keyword['exclude'][] = preg_quote($value, '/');
                } else {
                    $keyword['include'][] = preg_quote($value, '/');
                }
            }
        }

        return $keyword;
    }

    public function getSearchStatement(Array $search_array)
    {
        $result = array();
        $result['include'] = (isset($search_array['include'][0])) ? sprintf('+"%s"', implode('" +"', $search_array['include'])) : '';
        $result['exclude'] = (isset($search_array['exclude'][0])) ? sprintf('-"%s"', implode('" -"', $search_array['exclude'])) : '';

        return $result;
    }

    /**
     * Search album via FullText Index
     * @param string $param
     *
     * @return array
    */
    public function getSearchResult($param)
    {
        $result = array();
        if ($this->connectdb !== null) {
            $read['query'] = 'SELECT ai.id,ai.name,a.cover,a.parent_tree,a.property,a.last_edit,a.post_date ';
            $read['query'] .= 'FROM `'.$this->table['name'].'` AS ai '; 
            $read['query'] .= 'INNER JOIN '.$this->table['source'].' AS a ON a.id = ai.id ';
            $read['query'] .= 'WHERE MATCH(ai.name) AGAINST(? IN BOOLEAN MODE) ';
            $read['query'] .= 'ORDER BY ai.id DESC';
            $search_param = $param['include'].' '.$param['exclude'];
            $search_param = trim($search_param);
            try {
                $read['stmt'] = $this->connectdb->prepare($read['query']);
                $read['stmt']->bindValue(1, $search_param, \PDO::PARAM_STR);
                $read['stmt']->execute();
                $result = $read['stmt']->fetchAll(\PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                self::throwDBError($e->getMessage(), $e->getCode());
            }
        }

        return isset($result[0]) ? $result : false;
    }

    //Throw database error excetpion
    private static function throwDBError(string $message, mixed $code = null)
    {
        $error = '<h1>Service unavailable</h1>'."\n";
        $error .= '<h2>Error Info :'.$message.'</h2>'."\n";
        if ($code !== null) {
            $error .= '<h3>Error Code :'.$code.'</h3>'."\n";
        }

        throw new Exception($error);
    }
}
