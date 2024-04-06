<?php
namespace System;

class Load
{
    private $files;
    const DB_READ = 'db.read';
    const DB_UPDATE = 'db.update';
    const DB_CREATE = 'db.create';
    const DB_DELETE = 'db.delete';
    const CRUD = array(self::DB_READ, self::DB_UPDATE, self::DB_CREATE, self::DB_DELETE);

    public function loadClass(...$args)
    {
        $this->files = $args;
        foreach ($this->files as $file) {
            require dirname(__FILE__).'/class.'.$file.'.php';
        }
    }

    public function loadFunction(...$args)
    {
        $this->files = $args;
        foreach ($this->files as $file) {
            require dirname(dirname(__FILE__)).'/function/func.'.$file.'.php';
        }
    }

    public function loadDBClass(...$args)
    {
        $this->files = $args;
        if (is_array($this->files[0])) {
            $this->files = $this->files[0];
        }
        foreach ($this->files as $file) {
            require dirname(__FILE__).'/Database/class.'.$file.'.php';
        }
    }
}
