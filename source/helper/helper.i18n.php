<?php
namespace carry0987\Helper;

use carry0987\I18n\I18n;
use carry0987\I18n\Exception\IOException;

class I18nHelper extends Helper
{
    private $path = '/';
    private static $lang;
    private static $alias = array('en_US' => 'English', 'zh_TW' => '繁體中文');

    public function __construct(array $config)
    {
        if (isset($config['cookiePath'])) {
            $this->path = $config['cookiePath'].'/';
        }
        $config['useAutoDetect'] = true;
        $config['defaultLang'] = $this->getWebLanguage($config['systemConfig'] ?? null);
        $config['cookie'] = array(
            'name' => 'language',
            'expire' => time()+864000,
            'path' => $this->path,
            'domain' => '',
            'secure' => $config['HTTPS'],
            'httponly' => true
        );
        self::$lang = new I18n($config);
        self::setLangList(self::$alias);
    }

    private function setCookie(string $lang, bool $security = false)
    {
        $domain = (string) null;

        return setcookie('language', $lang, time()+864000, $this->path, $domain, $security, true);
    }

    public static function setLangList(array $lang_list)
    {
        self::$lang->setLangAlias($lang_list);
    }

    public static function getLangList()
    {
        return self::$lang->fetchLangList();
    }

    public function getLinks(array $params = array())
    {
        $query_url = '';
        if (!empty($params) === true) {
            unset($params['lang']);
            $query_url = '?'.http_build_query($params);
        }

        return $query_url;
    }

    public function loadLanguage(string $language)
    {
        $language = self::formatAcceptLanguage($language);

        return $language;
    }

    public static function formatAcceptLanguage(string $acceptLanguage)
    {
        if (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $acceptLanguage)) {
            return $acceptLanguage;
        }
        $langs = explode(',', $acceptLanguage);
        $primaryLang = explode(';', $langs[0])[0];
        $parts = explode('-', $primaryLang);
        if (count($parts) === 2) {
            return strtolower($parts[0]) . '_' . strtoupper($parts[1]);
        }

        return '';
    }

    public function getLang(string $key)
    {
        try {
            return self::$lang->fetch($key);
        } catch (IOException $e) {
            return null;
        }
    }

    public function getLangs()
    {
        return self::$lang->fetchList();
    }

    public function getCurrentLang()
    {
        return self::$lang->fetchCurrentLang();
    }

    public function setLanguage(string $lang, bool $security = false)
    {
        foreach (self::$lang->getLangAlias() as $key => $value) {
            if ($lang === $key) {
                return $this->setCookie($key, $security);
            }
        }

        return $this->setCookie('en_US', $security);
    }

    public function getWebLanguage(array $web_config = null)
    {
        $lang = 'en_US';
        if (isset($web_config['web_language']) && $web_config['web_language'] != '') {
            $lang = $web_config['web_language'];
        }

        return $lang;
    }
}
