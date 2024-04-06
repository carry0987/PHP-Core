<?php
namespace carry0987\Helper;

if (defined('IN_ADMIN') !== true) exit('Access Denied');

use \Core\Core;

class AdminHelper extends Helper
{
    private static $show_admin = false;
    public static $view_mode = array();
    public static $timezone_area = array(
        '1024' => 'UTC',
        '1' => 'Africa',
        '2' => 'America',
        '4' => 'Antarctica',
        '8' => 'Arctic',
        '16' => 'Asia',
        '32' => 'Atlantic',
        '64' => 'Australia',
        '128' => 'Europe',
        '256' => 'Indian',
        '512' => 'Pacific'
    );

    public function __construct($connect_db = null)
    {
        if ($connect_db) {
            parent::__construct($connect_db);
        }
        self::$view_mode = array(
            'global' => '',
            'member' => array(
                'list' => '',
                'group' => ''
            ),
            'tag' => array(
                'list' => '',
                'group' => ''
            ),
            'upload' => array(
                'file' => '',
                'thumbnail' => '',
                'size' => ''
            ),
            'display' => '',
            'search' => '',
            'security' => array(
                'signup' => '',
                'captcha' => '',
                'otp' => ''
            ),
            'email' => '',
            'database' => '',
            'redis' => ''
        );
    }

    public function fetchDatabaseInfo()
    {
        return array(
            'db_size' => checkDatabaseSize($this->connect_db, DB_NAME),
            'db_version' => $this->connect_db->getAttribute(\PDO::ATTR_SERVER_VERSION)
        );
    }

    public static function showAdmin(bool $value = null)
    {
        self::$show_admin = $value ?? self::$show_admin;

        return self::$show_admin;
    }

    public static function setCurrentMode(string $key, string $value = 'active')
    {
        if (strpos($key, '-') !== false) {
            list($part1, $part2) = explode('-', $key);
            if (!isset(self::$view_mode[$part1]) || !is_array(self::$view_mode[$part1])) {
                return false;
            }
            if (!isset(self::$view_mode[$part1][$part2])) {
                return false;
            }
            self::$view_mode[$part1][$part2] = $value;
        } else {
            if (is_array(self::$view_mode[$key])) {
                return false;
            }
            self::$view_mode[$key] = $value;
        }
    }

    public static function getCurrentMode(string $key)
    {
        if (strpos($key, '-') !== false) {
            list($part1, $part2) = explode('-', $key);
            return self::$view_mode[$part1][$part2] ?? null;
        } else {
            return self::$view_mode[$key] ?? null;
        }
    }

    public static function fetchTimezone($web_tz, $area_id)
    {
        $get_area = (!empty($area_id)) ? Utils::inputFilter($area_id) : 2047;
        $web_tz_list = $web_tz->getTimezoneList($get_area);
        $get_tz = false;
        if (is_array($web_tz_list)) {
            $get_tz = array('current_tz' => false, 'tz_list' => array());
            foreach ($web_tz_list as $value) {
                if ($value['zone'] === self::getSystem('system_timezone')) {
                    $get_tz['current_tz'] = $value['zone'];
                }
                $get_tz['tz_list'][$value['zone']] = $value['diff_from_GMT'];
            }
        }

        return $get_tz;
    }

    public static function fetchNumberOfMember()
    {
        return parent::$dataRead->getUserCount();
    }

    public static function updateGlobalConfig(array $data)
    {
        $get_config = self::$system['config']->getConfig(Core::SYSTEM_CONFIG, true);
        $get_config = Utils::arraySanitize(array(
            'web_name' => $data['web_name'],
            'web_description' => $data['web_description'],
            'web_language' => $data['web_lang'],
            'web_timezone' => $data['web_tz']
        ));
        $get_config['maintenance'] = (int) ($data['maintenance'] ?? 0);

        return self::$system['config']->updateConfig(Core::SYSTEM_CONFIG, $get_config);
    }

    public static function updateUploadConfig(string $config_key, array $data)
    {
        $config = self::$system['config']->getConfig($config_key, true);
        switch ($config_key) {
            case 'upload_config':
                $config['enable'] = (int) ($data['enable'] ?? $config['enable']);
                $config['image_library'] = $data['image_library'] ?? $config['image_library'];
                $config['sharp_server'] = rtrim((string) ($data['sharp_server'] ?? $config['sharp_server']), '/');
                $config['sharp_key'] = $data['sharp_key'] ?? $config['sharp_key'];
                $config['sharp_salt'] = $data['sharp_salt'] ?? $config['sharp_salt'];
                $config['sharp_encryption_key'] = $data['sharp_encryption_key'] ?? $config['sharp_encryption_key'];
                break;
            case 'upload_local':
                $get_config = Utils::arraySanitize($data, array('local_dir', 'local_url', 'max_size', 'allowed_ext', 'disallowed_ext'));
                $config['local_dir'] = $get_config['local_dir'] ?? $config['local_dir'];
                $config['local_url'] = $get_config['local_url'] ?? $config['local_url'];
                $config['max_size'] = (int) ($get_config['max_size'] ?? $config['max_size']);
                $config['allowed_ext'] = $get_config['allowed_ext'] ?? $config['allowed_ext'];
                $config['disallowed_ext'] = $get_config['disallowed_ext'] ?? $config['disallowed_ext'];
                break;
            case 'image_compression':
                $config['small']['width'] = (int) ($data['small'] ?? $config['small']['width']);
                $config['medium']['width'] = (int) ($data['medium'] ?? $config['medium']['width']);
                $config['large']['width'] = (int) ($data['large'] ?? $config['large']['width']);
                break;
            default:
                return false;
                break;
        }

        return self::$system['config']->updateConfig($config_key, $config);
    }

    public static function updateDisplayConfig(?array $data)
    {
        if (empty($data)) return false;
        $get_config = self::$system['config']->getConfig('display_config', true);
        $get_config['show_original_image'] = (int) ($data['show_original_image'] ?? 0);
        $get_config['use_webp_instead'] = (int) ($data['use_webp_instead'] ?? 0);
        $get_config['misses_per_page'] = (int) ($data['misses_per_page'] ?? 0);

        return self::$system['config']->updateConfig('display_config', $get_config);
    }

    public static function updateSearchConfig(?array $data)
    {
        if (empty($data)) return false;
        $get_config = self::$system['config']->getConfig('search_config', true);
        $get_config['enable'] = (int) ($data['enable'] ?? 0);
        $get_config['advanced_search'] = (int) ($data['advanced_search'] ?? 0);
        $get_config['min_length'] = (int) ($data['min_length'] ?? 0);

        return self::$system['config']->updateConfig('search_config', $get_config);
    }

    public static function updateSecurityConfig(string $config_key, array $data)
    {
        $config = self::$system['config']->getConfig($config_key, true);
        switch ($config_key) {
            case 'signup_config':
                $config['signup_feature'] = (int) ($data['signup_feature'] ?? $config['signup_feature']);
                $config['with_line'] = (int) ($data['with_line'] ?? $config['with_line']);
                $config['with_tg'] = (int) ($data['with_tg'] ?? $config['with_tg']);
                $config['line_channel_id'] = $data['line_channel_id'] ?? $config['line_channel_id'];
                $config['line_channel_secret'] = $data['line_channel_secret'] ?? $config['line_channel_secret'];
                $config['line_redirect_uri'] = $data['line_redirect_uri'] ?? $config['line_redirect_uri'];
                $config['line_scope'] = $data['line_scope'] ?? $config['line_scope'];
                $config['tg_bot_token'] = $data['tg_bot_token'] ?? $config['tg_bot_token'];
                $config['tg_bot_username'] = $data['tg_bot_username'] ?? $config['tg_bot_username'];
                $config['tg_redirect_uri'] = $data['tg_redirect_uri'] ?? $config['tg_redirect_uri'];
                break;
            case 'captcha_config':
                $config['enable'] = (int) ($data['enable'] ?? $config['enable']);
                $config['type'] = $data['type'] ?? $config['type'];
                // Check apply list
                $apply_list = array();
                if (isset($data['login']) && (int) $data['login'] === 1) $apply_list[] = 'login';
                if (isset($data['signup']) && (int) $data['signup'] === 1) $apply_list[] = 'signup';
                $config['apply'] = serialize($apply_list);
                // Simple captcha config
                $captcha_data = $data['simple_captcha'] ?? array();
                $simple_captcha = self::$system['config']->getConfig('simple_captcha', true);
                $simple_captcha['image_height'] = (int) ($captcha_data['image_height'] ?? $simple_captcha['image_height']);
                $simple_captcha['image_width'] = (int) ($captcha_data['image_width'] ?? $simple_captcha['image_width']);
                $simple_captcha['font_file'] = $captcha_data['font_file'] ?? $simple_captcha['font_file'];
                $simple_captcha['text_color'] = $captcha_data['text_color'] ?? $simple_captcha['text_color'];
                $simple_captcha['noise_color'] = $captcha_data['noise_color'] ?? $simple_captcha['noise_color'];
                $simple_captcha['total_character'] = (int) ($captcha_data['total_character'] ?? $simple_captcha['total_character']);
                $simple_captcha['random_dots'] = (int) ($captcha_data['random_dots'] ?? $simple_captcha['random_dots']);
                $simple_captcha['random_lines'] = (int) ($captcha_data['random_lines'] ?? $simple_captcha['random_lines']);
                $simple_captcha['check_sensitive'] = (int) ($captcha_data['check_sensitive'] ?? $simple_captcha['check_sensitive']);
                self::$system['config']->updateConfig('simple_captcha', $simple_captcha);
                // reCAPTCHA config
                $captcha_data = $data['google_recaptcha'] ?? array();
                $recaptcha = self::$system['config']->getConfig('google_recaptcha', true);
                $recaptcha['site_key'] = $captcha_data['site_key'] ?? $recaptcha['site_key'];
                $recaptcha['secret_key'] = $captcha_data['secret_key'] ?? $recaptcha['secret_key'];
                self::$system['config']->updateConfig('google_recaptcha', $recaptcha);
                // SVGCaptcha config
                $captcha_data = $data['svg_captcha'] ?? array();
                $svg_captcha = self::$system['config']->getConfig('svg_captcha', true);
                $svg_captcha['image_height'] = (int) ($captcha_data['image_height'] ?? $svg_captcha['image_height']);
                $svg_captcha['image_width'] = (int) ($captcha_data['image_width'] ?? $svg_captcha['image_width']);
                $svg_captcha['total_character'] = (int) ($captcha_data['total_character'] ?? $svg_captcha['total_character']);
                $svg_captcha['difficulty'] = (int) ($captcha_data['difficulty'] ?? $svg_captcha['difficulty']);
                self::$system['config']->updateConfig('svg_captcha', $svg_captcha);
                break;
            case 'otp_config':
                $config['enable'] = (int) ($data['enable'] ?? $config['enable']);
                $config['title'] = $data['title'] ?? $config['title'];
                break;
            default:
                return false;
                break;
        }

        return self::$system['config']->updateConfig($config_key, $config);
    }

    public static function updateEmailConfig(?array $data)
    {
        if (empty($data)) return false;
        $email_config = self::$system['config']->getConfig('email_config', true);
        $email_config['enable'] = (int) ($data['enable'] ?? $email_config['enable']);
        $email_config['type'] = $data['type'] ?? $email_config['type'];
        if (!empty($data['allow_domain']) && is_array($data['allow_domain'])) {
            $email_config['allow_domain'] = implode('|', $data['allow_domain']);
        }
        if (!empty($data['disallow_domain']) && is_array($data['disallow_domain'])) {
            $email_config['disallow_domain'] = implode('|', $data['disallow_domain']);
        }
        // SMTP config
        if (!empty($data['smtp']) && is_array($data['smtp'])) {
            $smtp_data = $data['smtp'];
            $smtp_config = self::$system['config']->getConfig('email_smtp', true);
            $smtp_config['charset'] = $smtp_data['charset'] ?? $smtp_config['charset'];
            $smtp_config['smtp_host'] = $smtp_data['smtp_host'] ?? $smtp_config['smtp_host'];
            $smtp_config['smtp_secure'] = $smtp_data['smtp_secure'] ?? $smtp_config['smtp_secure'];
            $smtp_config['smtp_port'] = (int) ($smtp_data['smtp_port'] ?? $smtp_config['smtp_port']);
            $smtp_config['smtp_user'] = $smtp_data['smtp_user'] ?? $smtp_config['smtp_user'];
            $smtp_config['smtp_pw'] = $smtp_data['smtp_pw'] ?? $smtp_config['smtp_pw'];
            $smtp_config['smtp_send_from'] = $smtp_data['smtp_send_from'] ?? $smtp_config['smtp_send_from'];
            $smtp_config['smtp_send_name'] = $smtp_data['smtp_send_name'] ?? $smtp_config['smtp_send_name'];
            self::$system['config']->updateConfig('email_smtp', $smtp_config);
        }
        // Localhost config
        if (!empty($data['localhost']) && is_array($data['localhost'])) {
            $local_data = $data['localhost'];
            $local_config = self::$system['config']->getConfig('email_localhost', true);
            $local_config['charset'] = $local_data['charset'] ?? $local_config['charset'];
            $local_config['port'] = (int) ($local_data['port'] ?? $local_config['port']);
            $local_config['send_from'] = $local_data['send_from'] ?? $local_config['send_from'];
            $local_config['send_name'] = $local_data['send_name'] ?? $local_config['send_name'];
            self::$system['config']->updateConfig('email_localhost', $local_config);
        }

        return self::$system['config']->updateConfig('email_config', $email_config);
    }
}
