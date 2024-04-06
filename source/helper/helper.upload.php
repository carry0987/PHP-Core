<?php
namespace carry0987\Helper;

use carry0987\Image\Image as Image;
use carry0987\Helper\ImageHelper;

class UploadHelper extends Helper
{
    private static $local_config = array(
        'local_dir' => array(
            'type' => self::TYPE_TEXT,
            'limit' => 250
        ),
        'local_url' => array(
            'type' => self::TYPE_TEXT,
            'limit' => 250
        ),
        'max_size' => array(
            'type' => self::TYPE_NUMBER,
            'limit' => 8
        ),
        'allowed_ext' => array(
            'type' => self::TYPE_TEXTAREA,
            'limit' => 0
        ),
        'disallowed_ext' => array(
            'type' => self::TYPE_TEXTAREA,
            'limit' => 0
        )
    );
    private static $sharp_config = array(
        'sharp_server' => array(
            'type' => self::TYPE_TEXT,
            'limit' => 300
        ),
        'sharp_key' => array(
            'type' => self::TYPE_TEXT,
            'limit' => 250
        ),
        'sharp_salt' => array(
            'type' => self::TYPE_TEXT,
            'limit' => 250
        ),
        'sharp_encryption_key' => array(
            'type' => self::TYPE_TEXT,
            'limit' => 250
        )
    );
    private static $image_library_list = array(
        'GD' => Image::LIBRARY_GD,
        'Imagick' => Image::LIBRARY_IMAGICK,
        'Sharp' => ImageHelper::SHARP_API
    );

    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_NUMBER = 'number';

    public function __construct($connect_db = null)
    {
        if ($connect_db) {
            parent::__construct($connect_db);
        }
    }

    public static function setLocalConfig(array $config)
    {
        // Set value from $config to $local_config
        foreach ($config as $key => $value) {
            if (isset(self::$local_config[$key])) {
                self::$local_config[$key]['value'] = $value;
            }
        }
    }

    public static function getLocalConfig()
    {
        return self::$local_config;
    }

    public static function getSharpConfig(array $config)
    {
        // Set value from $config to $sharp_config
        foreach ($config as $key => $value) {
            if (isset(self::$sharp_config[$key])) {
                self::$sharp_config[$key]['value'] = $value;
            }
        }

        return self::$sharp_config;
    }

    public static function getUploadEnabled(array $config)
    {
        return $config['enable'] === 1 ? 'enable' : 'disable';
    }

    public static function getImageLibrary(array $config = null)
    {
        return empty($config) ? self::$image_library_list : $config['image_library'];
    }
}
