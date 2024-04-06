<?php
namespace carry0987\Helper;

use System\Check;
use carry0987\Auth\GoogleAuthenticator;
use carry0987\SessionManager\SessionManager;

/**
 *  AuthHelper class to assist with user authentication tasks
 *  such as login, registration, and password recovery.
 */
class AuthHelper extends Helper
{
    private static $allow_auth = [
        'set' => false,
        'local' => false,
        'social' => false,
        'line' => false,
        'tg' => false
    ];
    private const TWO_FACTOR_DISCREPANCY = 1;

    public function __construct($connect_db = null)
    {
        if ($connect_db) {
            parent::__construct($connect_db);
        }

        // Merge param
        self::setParam('check_otp', true);
    }

    public function checkLogin(?array $login): void
    {
        if (isset($login['username']) && $login['username'] !== false) {
            Utils::redirectURL('./');
        }
    }

    public function checkSignupConfig(bool $check_random = false): array
    {
        $allow_signup = self::$allow_auth;
        $signup_config = self::getSystem('config')->getConfig('signup_config', true);
        if ($signup_config !== false) {
            if ($check_random === true) {
                [$username, $password] = self::checkSignupRandom($signup_config);
            }
            if ($signup_config['signup_feature'] === 1) {
                $allow_signup['set'] = $allow_signup['local'] = true;
            }
            if ($signup_config['with_line'] === 1 || $signup_config['with_tg'] === 1) {
                $allow_signup['set'] = $allow_signup['social'] = true;
                if ($signup_config['with_line'] === 1) {
                    $allow_signup['line'] = true;
                }
                if ($signup_config['with_tg'] === 1) {
                    $allow_signup['tg'] = true;
                }
            }
        }

        return $check_random === true ? [$username, $password, $allow_signup] : $allow_signup;
    }

    public function checkAllowSignup(): bool
    {
        $allow_signup = $this->checkSignupConfig();

        return $allow_signup['set'] === true;
    }

    public function processSignup(array $postData, array $captcha): array
    {
        $session = self::getSystem('session');

        $signup_result = [
            'error' => false,
            'display' => 'view_error',
            'userdata' => null
        ];

        // Check if captcha is set
        if ($captcha['set'] === true) {
            $signup_result['error'] = $session->get('captcha_validated') !== true ? 'common.captcha_error' : false;
        }

        // Reset validation
        self::resetValidation($session);

        // Extract and sanitize user data from postData
        if ($session->get('random_signup') !== null) {
            $random = $session->get('random_signup');
            $username = $random['username'];
            $password = $password_confirm = $random['password'];
            // Remove random data from session
            $session->remove('random_signup');
        } else {
            $username = self::sanitizeUsername($postData['username'] ?? null);
            $password = $postData['password'] ?? null;
            $password_confirm = $postData['pdr'] ?? null;
        }

        // Check validity of username and password
        if ($signup_result['error'] === false) {
            $signup_result = $this->validateSignup($username, $password, $password_confirm, $signup_result);
        }

        // Check user language
        if ($signup_result['error'] === false) {
            $lang_list = self::getSystem('lang_list');
            $user_language = isset($lang_list[$postData['user_lang']]) ? $postData['user_lang'] : self::getSystem('system_lang');
        }

        // If no errors, proceed with signup
        if ($signup_result['error'] === false) {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            // Fill user data
            $current_time = time();
            $user_info = [
                'username' => $username,
                'password' => $password_hash,
                'group_id' => 4,
                'language' => $user_language,
                'online_status' => $current_time+5,
                'last_login' => $current_time,
                'join_date' => $current_time
            ];
            // Create user
            $user_created = parent::$dataCreate->createUser($user_info);
            // Check if user was created
            if ($user_created === true) {
                $signup_result['display'] = 'view_success';
                $signup_result['userdata'] = $user_info;
            } else {
                $signup_result['error'] = 'common.create_user_failed';
            }
        }

        return $signup_result;
    }

    public function processLogin(array $postData, array $captcha = null): array
    {
        $session = self::getSystem('session');

        $login_result = [
            'error' => false,
            'display' => 'view_error',
            'userdata' => null
        ];

        // Check if captcha is set
        if ($captcha !== null && $captcha['set'] === true) {
            $login_result['error'] = $session->get('captcha_validated') !== true && !$session->exists('user_valid') ? 'common.captcha_error' : false;
        }

        // Two Factor config
        $otp_config = ['enable' => 0];
        if (self::getParam('check_otp') === true) {
            $otp_config = self::getSystem('config')->getConfig('otp_config', true);
        }

        // Start login process
        if ($login_result['error'] === false && !$session->exists('user_valid')) {
            $account = $postData['account'];
            $get_password = $postData['password'];
            // Reset validation
            self::resetValidation($session);
            // Find user
            $user = parent::$dataRead->getUserLogin($account);
            if (!$user) {
                $login_result['error'] = 'error.account_not_exist';
            } elseif (!password_verify($get_password, $user['password'])) {
                $login_result['error'] = 'error.wrong_password';
            } else {
                $login_result['userdata'] = $user;
                // Check if two factor is enabled
                if ($otp_config['enable'] === 1 && $user['OTP'] !== null) {
                    $session->set('user_valid', Utils::inputFilter($user['username']));
                } else {
                    // Complete login process
                    $login_result['display'] = 'view_success';
                    $this->finalizeLogin($user, $postData);
                }
            }
        }

        return $login_result;
    }

    public function processOTP(array $postData): array
    {
        $session = self::getSystem('session');
        self::resetValidation($session, 'captcha');
        if (!$session->exists('user_valid')) {
            return ['error' => false, 'display' => 'view_success'];
        }
        if (!isset($postData['auth'])) {
            return ['error' => false, 'display' => 'view_otp'];
        }

        $login_result = [
            'error' => 'error.username_not_exist',
            'display' => 'view_error'
        ];

        $googleAuthenticator = new GoogleAuthenticator();
        $user_valid = $session->get('user_valid');
        // Reset validation
        self::resetValidation($session);
        $user = parent::$dataRead->getUserLogin($user_valid);
        if ($user !== false) {
            $login_result['error'] = 'error.otp_invalid';
            if ($googleAuthenticator->verifyCode($user['OTP'], $postData['auth'], self::TWO_FACTOR_DISCREPANCY) === true) {
                $login_result['error'] = false;
                $login_result['display'] = 'view_success';
                $this->finalizeLogin($user, $postData);
            }
        }

        return $login_result;
    }

    public static function fetchPostData(string $key): mixed
    {
        if (!isset($_POST[$key])) {
            return null;
        }

        $session = self::getSystem('session');
        $csrf_token = $_POST['csrf_token'] ?? null;
        if ($session->verifyCSRFToken($csrf_token) !== true) {
            $i18n = self::getSystem('i18n');
            throw new \Exception($i18n->getLang('error.csrf_token_invalid'));
        }

        return $_POST[$key];
    }

    private static function resetValidation(SessionManager $session, string $type = null): void
    {
        switch ($type) {
            case 'captcha':
                $session->remove('captcha_validated');
                break;
            case 'user':
                $session->remove('user_valid');
                break;
            default:
                $session->remove('captcha_validated');
                $session->remove('user_valid');
                break;
        }
    }

    private function finalizeLogin(array $user, array $postData)
    {
        // Set username to session
        $session = self::getSystem('session');
        $session->set('username', $user['username']);
        // Reset validation
        self::resetValidation($session);

        // Check if "remember me" cookie should be set
        $rememberMe = self::getSystem('rememberMe');
        if (!empty($postData['remember_me'])) {
            // Set times
            $cookie_expiration_time = time() + (30 * 24 * 60 * 60); // 30 days
            $year_time = time() + (1 * 365 * 24 * 3600);
            // Start remember me process
            $rememberMe->setAuthCookie('user_login', $user['uid'], $year_time);
            $random_password = $rememberMe->getToken(16);
            $rememberMe->setAuthCookie('random_pw', $random_password, $cookie_expiration_time);
            $random_pw_hash = password_hash($random_password, PASSWORD_DEFAULT);
            $expiry_date = $cookie_expiration_time;
            $selector = (isset($_COOKIE['random_selector'])) ? $_COOKIE['random_selector'] : 0;
            // Mark existing token as expired
            $userToken = $rememberMe->getTokenByUserID($user['uid'], $selector);
            if ($userToken !== false) {
                $rememberMe->updateToken($user['uid'], $selector, $random_pw_hash);
            } else {
                $random_selector = $rememberMe->getToken(16);
                $rememberMe->setAuthCookie('random_selector', $random_selector, $year_time);
                // Insert new token
                $rememberMe->insertToken($user['uid'], $random_selector, $random_pw_hash, $expiry_date);
            }
        } else {
            $rememberMe->clearAuthCookie();
        }
    }

    private static function sanitizeUsername(?string $username): ?string
    {
        $username = Utils::inputFilter($username);
        $username = str_replace(' ', '_', $username);

        return preg_replace('/[^0-9A-Za-z_]/', '', $username);
    }

    private static function validateSignup(string $username, string $password, string $password_confirm, array $signup_result): array
    {
        // Assume the method below checks if username is already in use
        $account_check = new Check(self::getSystem('sanite'));
        if (empty($username)) {
            $signup_result['error'] = 'common.username_empty';
        } else if ($account_check->checkUsername($username) !== true) {
            $signup_result['error'] = 'common.duplicate_username';
        } else if (empty($password) || empty($password_confirm)) {
            $signup_result['error'] = 'common.password_empty';
        } else if ($password !== $password_confirm) {
            $signup_result['error'] = 'common.repassword_error';
        }

        return $signup_result;
    }

    private static function checkSignupRandom(array|bool $signup_config): array
    {
        $username = $password = null;
        if ($signup_config !== false) {
            $random = ['username' => false, 'password' => false];
            // Check random username
            if ($signup_config['random_username'] === 1) {
                $random['username'] = true;
                $account_check = new Check(self::getSystem('sanite'));
                $username = function($name) use (&$username, $account_check) {
                    if ($account_check->checkUsername($name) === true) {
                        return $name;
                    }
                    return $username(Utils::generateRandom(8));
                };
                $username = $username(Utils::generateRandom(8));
            }
            // Check random password
            if ($signup_config['random_pw'] === 1) {
                $random['password'] = true;
                $password = Utils::generateRandom(8);
            }
            // Store random data to session
            if ($random['username'] === true || $random['password'] === true) {
                $session = self::getSystem('session');
                $session->set('random_signup', $random);
            }
        }

        return [$username, $password];
    }
}
