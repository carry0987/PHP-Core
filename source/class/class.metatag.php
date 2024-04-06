<?php
namespace System;

class MetaTag
{
    private $main_config;
    private static $meta = array();

    public function __construct(array $config_array)
    {
        $this->main_config = $config_array;
    }

    public function getMainName()
    {
        return $this->main_config['web_name'];
    }

    public function getMainDescription()
    {
        return $this->main_config['web_description'];
    }

    public function setTitle($value)
    {
        self::$meta['title'] = $value;

        return $value;
    }

    public function setDescription($value)
    {
        self::$meta['description'] = $value;

        return $value;
    }

    public function setImage($value)
    {
        self::$meta['image'] = $value;

        return $value;
    }

    public static function getTitle()
    {
        return self::$meta['title'];
    }

    public static function getDescription()
    {
        return self::$meta['description'];
    }

    public static function getImage()
    {
        return self::$meta['image'];
    }
}
