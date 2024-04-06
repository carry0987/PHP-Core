<?php
namespace Core;

use System\ {
    Config,
    Check,
    MetaTag,
    Timezone
};
use carry0987\ {
    SessionManager\SessionManager,
    Sanite\Sanite,
    Redis\RedisTool,
    Template,
    Helper\Utils,
    Helper\I18nHelper,
    Helper\RememberMeHelper
};

class Core
{
    public array $host = [];
    public ?array $login = null;
    public array $LANG = [];
    public array $meta = [];
    private array $SYSTEM = [];
    private string $current_script = '';
    private \PDO $conn;
    private bool $redis;
    private array $system_config = [];
    private array $template_option = [];
    private array $lang_list = ['en_US' => 'English', 'zh_TW' => '繁體中文'];

    public const CORE_VERSION = '1.0.0';
    public const ROOT_PATH = __DIR__.'/../../../';
    public const CONFIG_FILE = 'config.inc.php';
    public const SYSTEM_CONFIG = 'web_config';

    public function __construct()
    {
        // Set header
        Utils::setHeader([
            'Content-Type' => 'text/html; charset=utf-8',
            'Set-Cookie' => 'SameSite=None; Secure; HttpOnly'
        ]);

        // Check config and include necessary files
        $this->checkAndIncludeConfigs();

        // Check for HTTPS protocol
        $this->checkHttpsProtocol();

        // Setup Redis
        $this->setupRedis();

        // Initialize Configuration
        $this->initializeConfig();

        // Check and set language
        $this->checkAndSetLanguage();

        // Check session
        $this->checkSession();

        // Perform system checks
        $this->performSystemChecks();

        // Check login user
        $this->checkLoginUser();

        // Set Meta Information
        $this->setupMetaInformation();

        // Set Timezone
        $this->setupTimezone();

        // Check for Maintenance mode
        $this->checkForMaintenance();
    }

    public function getSystem(): array
    {
        return $this->SYSTEM;
    }

    public function getConnection(): \PDO
    {
        return $this->conn;
    }

    public function getHost(string $key = null): mixed
    {
        if ($key !== null) {
            return $this->host[$key] ?? null;
        }

        return $this->host;
    }

    public function getMetaInfo(string $key = null): mixed
    {
        if ($key !== null) {
            return $this->meta[$key] ?? null;
        }

        return $this->meta;
    }

    public function getSystemCommon(): array
    {
        return [
            $this->host['base_url'],
            $this->login,
            $this->meta,
            $this->conn,
            $this->LANG
        ];
    }

    public function setTemplate(array $config = []): Template\Template
    {
        // Template setting
        $options = [
            'template_dir' => 'theme/default/common/',
            'css_dir' => 'theme/default/common/dist/',
            'js_dir' => 'theme/default/common/dist/',
            'static_dir' => 'static/',
            'cache_dir' => 'data/cache/',
            'auto_update' => true,
            'cache_lifetime' => 0
        ];

        $this->template_option = array_merge($options, $config);

        $template = new Template\Template();
        $template->setOptions($this->template_option);
        $template->setDatabase(new Template\Controller\DBController($this->conn));
        $template->setRedis(new Template\Controller\RedisController($this->SYSTEM['redis']));

        return $template;
    }

    private function checkAndIncludeConfigs(): void
    {
        //Check config
        if (file_exists(self::ROOT_PATH.'/config/'.self::CONFIG_FILE) === false) {
            if (file_exists(self::ROOT_PATH.'/install/index.php')) {
                if (file_exists(self::ROOT_PATH.'/config/installed.lock') === false) {
                    Utils::redirectURL('./install');
                } else {
                    echo '<h1>Program installed but could not find the config file !</h1>',"\n";
                    echo '<h2>Please put '.self::CONFIG_FILE.' file to "config" folder </h2>',"\n";
                    echo '<h3>If you want to reinstall, just remove "installed.lock" file from "config" &amp; "install" folder</h3>',"\n";
                    echo '<h3>Then go to install page</h3>',"\n";
                }
            } else {
                    echo '<h1>Could not find the config file !</h1>',"\n";
                    echo '<h2>Please put '.self::CONFIG_FILE.' file to "config" folder </h2>';
            }
            exit();
        } else {
            require self::ROOT_PATH.'/config/'.self::CONFIG_FILE;
            require self::ROOT_PATH.'/source/version.php';
            if (defined('DEBUG') && DEBUG) {
                error_reporting(E_ALL | E_STRICT);
                ini_set('display_errors', 1);
                ini_set('display_startup_errors', 1);
            }
            $this->current_script = basename($_SERVER['SCRIPT_NAME'], '.php');
            $this->SYSTEM['sanite'] = new Sanite(DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, 'utf8mb4', DB_PORT);
            $this->conn = $this->SYSTEM['sanite']->getConnection();
        }
    }

    private function checkHttpsProtocol(): void
    {
        //Check HTTPS
        $this->SYSTEM['https'] = false;
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') {
            $this->SYSTEM['https'] = true;
        } elseif (isset($_SERVER['HTTP_CF_VISITOR'])) {
            $this->SYSTEM['visitor'] = json_decode($_SERVER['HTTP_CF_VISITOR']);
            if ($this->SYSTEM['visitor']->scheme == 'https') $this->SYSTEM['https'] = true;
            unset($this->SYSTEM['visitor']);
        }
        $this->host['link'] = ($this->SYSTEM['https']?'https':'http').'://'.$_SERVER['HTTP_HOST'];
        $this->host['base_url'] = $this->host['link'].SYSTEM_PATH;
    }

    private function setupRedis(): void
    {
        // Redis
        $this->SYSTEM['redis'] = null;
        $redis = ['REDIS_HOST', 'REDIS_PORT', 'REDIS_DATABASE', 'REDIS_PASSWORD'];
        foreach ($redis as $value) {
            if (!defined($value) || (constant($value) === '' && $value !== 'REDIS_PASSWORD')) {
                $this->SYSTEM['redis'] = false;
            }
        }
        $this->redis = true;
        if ($this->SYSTEM['redis'] === null) {
            try {
                $this->SYSTEM['redis'] = new RedisTool(['host' => REDIS_HOST, 'port' => REDIS_PORT, 'password' => REDIS_PASSWORD, 'database' => REDIS_DATABASE]);
            } catch (\Throwable $e) {
                $this->redis = false;
            }
        }
    }

    private function initializeConfig(): void
    {
        // Get Config
        $this->SYSTEM['config'] = new Config($this->conn);
        $this->system_config = $this->SYSTEM['config']->getConfig(self::SYSTEM_CONFIG, true);

        // Set Redis
        if ($this->redis) {
            $this->SYSTEM['config']->setRedis($this->SYSTEM['redis']);
        }
    }

    private function checkAndSetLanguage(): void
    {
        // Check language
        $this->SYSTEM['i18n'] = new I18nHelper([
            'systemConfig' => $this->system_config,
            'HTTPS' => $this->SYSTEM['https'],
            'cookiePath' => SYSTEM_PATH,
            'langFilePath' => self::ROOT_PATH.'language',
            'cachePath' => 'data/cache/lang',
        ]);
        I18nHelper::setLangList($this->lang_list);
        $this->SYSTEM['lang_list'] = I18nHelper::getLangList();
        $this->SYSTEM['system_lang'] = $this->SYSTEM['i18n']->getCurrentLang();
        $this->LANG = $this->SYSTEM['i18n']->getLangs();
    }

    private function checkSession(): void
    {
        // Check session
        $this->SYSTEM['session'] = null;
        if (!defined('SESSION_ID')) {
            echo '<h1>',$this->SYSTEM['i18n']->getLang('common.session_error'),'</h1>';
            echo '<br />';
            echo '<h2>',$this->SYSTEM['i18n']->getLang('common.please'),' <a href="./install" style="color: blue;">',$this->SYSTEM['i18n']->getLang('common.reinstall'),'</a> System !</h2>';
            define('SESSION_ID', 'sessionerror');
            exit();
        } else {
            $this->SYSTEM['session'] = new SessionManager(SESSION_ID);
        }
    }

    private function performSystemChecks(): void
    {
        // System check
        $this->SYSTEM['check'] = new Check($this->SYSTEM['sanite']);
    }

    private function checkLoginUser(): void
    {
        // Check login user
        $this->SYSTEM['rememberMe'] = new RememberMeHelper;
        $this->SYSTEM['rememberMe']->setConnection($this->conn)->setPath(SYSTEM_PATH);
        if (!empty($this->SYSTEM['session']->get('username'))) {
            $this->login = $this->SYSTEM['check']->checkUserLoginByName($this->SYSTEM['session']->get('username'));
            if (is_array($this->login)) {
                $this->login['admin'] = ($this->login['user_level'] >= 3) ? true : false;
            }
        } elseif (Utils::checkEmpty($_COOKIE, ['user_login', 'random_pw', 'random_selector'])) {
            $checkRemember = $this->SYSTEM['rememberMe']->verifyToken($_COOKIE['user_login'], $_COOKIE['random_selector'], $_COOKIE['random_pw']);
            if (!empty($checkRemember)) {
                $checkRemember = $this->SYSTEM['check']->checkUserLoginByID((int) $_COOKIE['user_login']);
                $this->SYSTEM['session']->set('username', $checkRemember['username']);
                $this->login = $this->SYSTEM['check']->checkUserLoginByName($this->SYSTEM['session']->get('username'));
                if (is_array($this->login)) {
                    $this->login['admin'] = ($this->login['user_level'] >= 3) ? true : false;
                }
            }
        }
    }

    private function setupMetaInformation(): void
    {
        // MetaInfo
        $metaInfo = new MetaTag($this->system_config);
        // Meta site name and url
        $meta['name'] = $metaInfo->getMainName();
        $meta['url'] = ($this->SYSTEM['https']?'https':'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        // Meta Title
        $meta['current_url'] = basename($_SERVER['SCRIPT_FILENAME'], '.php');
        switch ($meta['current_url']) {
            case 'login' === $meta['current_url']:
                $meta['title'] = $this->SYSTEM['i18n']->getLang('common.login').' | '.$meta['name'];
                break;
            case 'signup' === $meta['current_url']:
                $meta['title'] = $this->SYSTEM['i18n']->getLang('common.signup').' | '.$meta['name'];
                break;
            case 'logout' === $meta['current_url']:
                $meta['title'] = $this->SYSTEM['i18n']->getLang('common.logout').' | '.$meta['name'];
                break;
            default:
                $meta['title'] = $meta['name'];
                break;
        }
        // Meta Description & Image
        $meta['description'] = $metaInfo->getMainDescription();
        $meta['default_image'] = './static/icon/logo.png';
        $meta['image'] = $meta['default_image'];
        $meta['type'] = 'website';

        $this->meta = $meta;
    }

    private function setupTimezone(): void
    {
        // Get Timezone
        $timezone = new Timezone($this->system_config);
        $this->SYSTEM['system_timezone'] = $timezone->getWebTimezone();
        if (isset($this->login['uid'], $this->login['timezone'])) {
            $this->SYSTEM['user_timezone'] = $timezone->getCustomTimezone($this->login['timezone']);
        } else {
            $this->SYSTEM['user_timezone'] = false;
        }
    }

    private function checkForMaintenance(): void
    {
        // Maintenance
        $this->SYSTEM['maintenance'] = false;
        if ($this->system_config['maintenance'] === 1 && (!isset($this->login['admin']) || $this->login['admin'] !== true)) {
            $this->SYSTEM['maintenance'] = true;
            if ($this->current_script !== 'admin' && $this->current_script !== 'maintenance') {
                Utils::redirectURL('maintenance.php');
            }
        }
    }
}
