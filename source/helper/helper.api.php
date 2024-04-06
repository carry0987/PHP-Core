<?php
namespace carry0987\Helper;

use System\Check;
use carry0987\Helper\Utils;
use carry0987\RESTful\RESTful;
use carry0987\Falcon\Falcon;

class APIHelper extends Helper
{
    private static $request = array(
        self::REQUEST_GET => array(),
        self::REQUEST_POST => array()
    );

    const REQUEST_GET = 'GET';
    const REQUEST_POST = 'POST';

    public function __construct($connect_db = null)
    {
        if ($connect_db) {
            parent::__construct($connect_db);
        }
    }

    public static function setRequest(array $post, array $get)
    {
        self::$request[self::REQUEST_GET] = $get;
        self::$request[self::REQUEST_POST] = $post;
    }

    public static function fetchResult(string $method)
    {
        if ($method === self::REQUEST_POST) {
            $data = self::$request[self::REQUEST_POST];
            // Get POST data
            if (!isset($data['request'])) return false;
            return self::processPostRequest($data);
        }
        if ($method === self::REQUEST_GET) {
            $data = self::$request[self::REQUEST_GET];
            // Get GET data
            if (!isset($data['request'])) return false;
            return self::processGetRequest($data);
        }
    }

    private static function processPostRequest(array $data)
    {
        switch ($data['request']) {
            case 'refresh_last_login':
                if (isset(self::$param['login']['uid'])) {
                    return parent::$dataUpdate->updateLastlogin(self::$param['login']['uid'], time());
                }
                break;
            case 'get_language':
                return array('lang' => self::$param['lang']);
                break;
            case 'get_language_list':
                return self::$system['lang_list'];
                break;
            case 'set_language':
                self::$system['i18n']->setLanguage($data['lang'], self::$system['https']);
                break;
            case 'random_hash':
                return Utils::generateRandom((int) ($data['length'] ?? 8));
                break;
            case 'check_simple_captcha':
                $captchaHelper = new CaptchaHelper;
                $captchaHelper::setSystem(self::$system);
                $captchaHelper::setFontPath(dirname(__DIR__).'/../static/captcha/');
                $captchaHelper::setCaptchaConfig('simple_captcha', self::$param['simple_captcha']);
                return $captchaHelper->checkSimpleCaptcha($data['code'] ?? null);
                break;
            case 'check_svg_captcha':
                $captchaHelper = new CaptchaHelper;
                $captchaHelper::setSystem(self::$system);
                $captchaHelper::setFontPath(dirname(__DIR__).'/../static/captcha/');
                $captchaHelper::setCaptchaConfig('svg_captcha', self::$param['svg_captcha']);
                return $captchaHelper->checkSVGCaptcha($data['code'] ?? null);
                break;
            case 'check_google_recaptcha':
                $captchaHelper = new CaptchaHelper;
                $captchaHelper::setSystem(self::$system);
                $captchaHelper::setCaptchaConfig('google_recaptcha', self::$param['google_recaptcha']);
                return $captchaHelper->verifyGoogleRecaptcha($data['code'] ?? null, $_SERVER['REMOTE_ADDR']);
                break;
            case 'fetch_social_link':
            case 'fetch_social_user':
                $config = array('providers' => array());
                $providerName = null;
                $social = self::$param['signup_config'];
                if ($data['type'] === 'line' && self::$param['signup_config']['with_line'] === 1) {
                    $providerName = 'line';
                    $config['providers']['line'] = array(
                        'client_id' => $social['line_channel_id'],
                        'client_secret' => $social['line_channel_secret'],
                        'redirect_uri' => $social['line_redirect_uri']
                    );
                } elseif ($data['type'] === 'telegram' && self::$param['signup_config']['with_tg'] === 1) {
                    $providerName = 'telegram';
                    // Get bot id in bot token
                    $social['tg_bot_token'] = explode(':', $social['tg_bot_token']);
                    $social['bot_id'] = $social['tg_bot_token'][0];
                    $social['bot_token'] = $social['tg_bot_token'][1];
                    $config['providers']['telegram'] = array(
                        'client_id' => $social['bot_id'],
                        'client_secret' => $social['bot_token'],
                        'redirect_uri' => $social['tg_redirect_uri']
                    );
                } else {
                    return false;
                }
                $falcon = new Falcon($config);
                $provider = $falcon->createProvider($providerName);

                // Fetch social link
                if ($data['request'] === 'fetch_social_link') {
                    return $provider->authorize() ?? $falcon->getConfig($providerName)['redirect_uri'];
                }
                if ($data['type'] === 'line') {
                    $token = $provider->getAccessToken($data['code'], $data['state'] ?? null);
                } elseif ($data['type'] === 'telegram') {
                    $token = $data;
                }
                return $provider->getUser($token);
                break;
        }
    }

    private static function processGetRequest(array $data)
    {
        switch ($data['request']) {
            case 'random_image':
                // Get random image
                if (isset($data['key']) && $data['key'] === IMAGE_KEY) {
                    // Get image info
                    $result = parent::$dataRead->getRandomImage();
                    if ($result !== false && $result['misses_id'] !== null) {
                        if (file_exists($result['file_path'].'_s.'.$result['file_type'])) {
                            $result['file_path'] = self::$param['base_url'].'/'.$result['file_path'];
                            $result['file_path'] .= '_s.'.$result['file_type'];
                            $result = array('url' => $result['file_path'], 'misses_id' => $result['misses_id'], 'misses_name' => $result['name']);
                        }
                    }
                    return $result;
                }
                break;
            case 'get_simple_captcha':
                if (Utils::checkReferer() !== true) exit('Access Denied');
                $captchaHelper = new CaptchaHelper(self::$param['captcha_config']);
                $captchaHelper::setSystem(self::$system);
                $captchaHelper::setCaptchaConfig('simple_captcha', self::$param['simple_captcha']);
                $captchaHelper::setFontPath(dirname(__DIR__).'/../static/captcha/');
                $captcha = $captchaHelper->generateSimpleCaptcha();
                return $captcha;
                break;
            case 'get_svg_captcha':
                if (Utils::checkReferer() !== true) exit('Access Denied');
                $captchaHelper = new CaptchaHelper(self::$param['captcha_config']);
                $captchaHelper::setSystem(self::$system);
                $captchaHelper::setCaptchaConfig('svg_captcha', self::$param['svg_captcha']);
                $captcha = $captchaHelper->generateSVGCaptcha();
                return RESTful::encodeJSON($captcha);
                break;
            case 'check_username':
                if (Utils::checkReferer() !== true) exit('Access Denied');
                $accountCheck = new Check(self::getSystem('sanite'));
                return $accountCheck->checkUsername($data['username'] ?? null);
                break;
        }
    }
}
