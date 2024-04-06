<?php
namespace carry0987\Helper;

use carry0987\Captcha\SimpleCaptcha;
use carry0987\Captcha\SVGCaptcha;
use ReCaptcha\ReCaptcha;

class CaptchaHelper extends Helper
{
    private static $config;
    private static $captcha_config = array();
    private static $type_list = array('simple_captcha', 'google_recaptcha', 'svg_captcha');
    private static $difficulty = array('easy', 'medium', 'hard');

    public function __construct(array $config_array = null)
    {
        if ($config_array) {
            self::$config = $config_array;
        }
    }

    public static function getTypeList()
    {
        return self::$type_list;
    }

    public static function setCaptchaConfig(string $key, array $config_array)
    {
        self::$captcha_config[$key] = $config_array;
    }

    public static function getCaptchaConfig(string $key)
    {
        if (empty($key)) return self::$captcha_config;
        return (isset(self::$captcha_config[$key])) ? self::$captcha_config[$key] : null;
    }

    public static function getDifficulty()
    {
        return self::$difficulty;
    }

    public static function checkEnable()
    {
        return (self::$config['enable'] !== 1) ? false : true;
    }

    public static function getCurrentType()
    {
        return self::$config['type'];
    }

    public static function checkType(string $type)
    {
        return (self::$config['type'] === $type) ? true : false;
    }

    public static function checkApply(string $key = '')
    {
        if (self::checkEnable() === false) return false;

        $apply = unserialize(self::$config['apply']);
        if (empty($key)) return (!is_array($apply)) ? array() : $apply;

        return (is_array($apply) && (isset($apply[$key]) || in_array($key, $apply))) ? true : false;
    }

    public static function setFontPath(string $fontPath)
    {
        self::$captcha_config['font_path'] = $fontPath;
    }

    public static function getFontPath()
    {
        return self::$captcha_config['font_path'] ?? null;
    }

    public static function generateSimpleCaptcha()
    {
        $simple_captcha_config = self::getCaptchaConfig('simple_captcha');
        self::getSystem('session')->set('captcha_validated', false);
        if ($simple_captcha_config) {
            $simple_captcha_config['font_file'] = self::getFontPath().$simple_captcha_config['font_file'];
            $simpleCaptcha = new SimpleCaptcha;
            $simpleCaptcha->setCaptchaOption($simple_captcha_config);
            $captcha = $simpleCaptcha->generateCaptcha();
            self::getSystem('session')->set('captcha_valid_code', $captcha['code']);
            return $simpleCaptcha->getCaptchaBase64($captcha['captcha_image']);
        }

        return null;
    }

    public static function generateSVGCaptcha()
    {
        $svg_captcha_config = self::getCaptchaConfig('svg_captcha');
        self::getSystem('session')->set('captcha_validated', false);
        if ($svg_captcha_config) {
            $generateSVG = SVGCaptcha::getInstance(
                $svg_captcha_config['total_character'],
                $svg_captcha_config['image_height'],
                $svg_captcha_config['image_width'],
                (int) $svg_captcha_config['difficulty']
            );
            $captcha = $generateSVG->getSVGCaptcha();
            self::getSystem('session')->set('captcha_valid_code', $captcha[0]);
            return $captcha[1];
        }

        return null;
    }

    public static function checkSimpleCaptcha(string $userInput)
    {
        if (empty(self::$captcha_config['simple_captcha'])) {
            return false;
        }
        $sessionCaptchaCode = self::getSystem('session')->get('captcha_valid_code');
        self::$captcha_config['simple_captcha']['font_file'] = self::getFontPath().self::$captcha_config['simple_captcha']['font_file'];
        $simpleCaptcha = new SimpleCaptcha;
        $simpleCaptcha->setCaptchaOption(self::$captcha_config['simple_captcha']);
        $result = $simpleCaptcha->checkCaptcha($sessionCaptchaCode, $userInput);
        self::getSystem('session')->set('captcha_validated', $result);
        if ($result === true) self::removeCaptchaSession();

        return $result;
    }

    public static function checkSVGCaptcha(string $userInput)
    {
        if (self::getCaptchaConfig('svg_captcha') === null) {
            return false;
        }
        $sessionCaptchaCode = self::getSystem('session')->get('captcha_valid_code');
        $result = SVGCaptcha::checkCaptcha($sessionCaptchaCode, $userInput);
        self::getSystem('session')->set('captcha_validated', $result);
        if ($result === true) self::removeCaptchaSession();

        return $result;
    }

    public static function verifyGoogleRecaptcha(string $response, string $remoteIp)
    {
        $googleRecaptchaConfig = self::getCaptchaConfig('google_recaptcha');
        if ($googleRecaptchaConfig === null) {
            return false;
        }

        $recaptcha = new ReCaptcha($googleRecaptchaConfig['secret_key']);
        $result = $recaptcha->verify($response, $remoteIp)->isSuccess();
        self::getSystem('session')->set('captcha_validated', $result);
        if ($result === true) self::removeCaptchaSession();

        return $result;
    }

    public static function removeCaptchaSession()
    {
        self::getSystem('session')->remove('captcha_valid_code');
    }
}
