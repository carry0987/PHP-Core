<?php
namespace System;

use \Core\Core;
use carry0987\Config\Config as ConfigTool;

class Config extends ConfigTool
{
    public const CONFIG_TABLE = 'global_config';
    private static $configIndex = array(
        Core::SYSTEM_CONFIG => 1,
        'upload_config' => 2,
        'upload_local' => 3,
        'upload_remote' => 4,
        'captcha_config' => 5,
        'simple_captcha' => 6,
        'google_recaptcha' => 7,
        'svg_captcha' => 8,
        'seo_sitemap_config' => 9,
        'comment_config' => 10,
        'image_compression' => 11,
        'signup_config' => 12,
        'otp_config' => 13,
        'email_config' => 14,
        'email_localhost' => 15,
        'email_smtp' => 16,
        'display_config' => 17,
        'search_config' => 18
    );

    public function __construct(\PDO $connectDB)
    {
        $config = parent::__construct($connectDB);
        $config->setTableName(self::CONFIG_TABLE);
        $config->setIndexList(self::$configIndex);
    }
}
