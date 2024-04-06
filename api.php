<?php
require dirname(__FILE__).'/source/include/Core/Initializer.php';

//Get core
$core = new Core\Core;
$SYSTEM = $core->getSystem();
[$base_url, $login, $meta, $conn] = $core->getSystemCommon();

// Composer
use System\Load;
use carry0987\Helper\Helper;
use carry0987\Helper\APIHelper;
use carry0987\RESTful\RESTful;

// Load classes
$load = new Load;
$load->loadFunction('core');

// APIHelper
$helper = new APIHelper($conn);
$helper::setSystem($SYSTEM);
$helper::setParam('login', $login);
$helper::setParam('base_url', $base_url);
$helper::setParam('lang', $SYSTEM['i18n']->getLangs());
$result = false;

// Check request method
if ($method = RESTful::verifyHttpMethod(true)) {
    $helper::setRequest($_POST, $_GET);
    if (isset($_POST, $_POST['request'])) {
        switch ($_POST['request']) {
            case 'refresh_last_login':
                $load->loadDBClass(Load::DB_UPDATE);
                $helper->setDB(Helper::DB_UPDATE, new DataUpdate($SYSTEM['sanite']));
                break;
            case 'check_simple_captcha':
            case 'check_svg_captcha':
            case 'check_google_recaptcha':
                $helper::setParam('captcha_valid_code', $SYSTEM['session']->get('captcha_valid_code'));
                $helper::setParam('captcha_config', $SYSTEM['config']->getConfig('captcha_config', true));
                $helper::setParam('simple_captcha', $SYSTEM['config']->getConfig('simple_captcha', true));
                $helper::setParam('svg_captcha', $SYSTEM['config']->getConfig('svg_captcha', true));
                $helper::setParam('google_recaptcha', $SYSTEM['config']->getConfig('google_recaptcha', true));
                break;
            case 'fetch_social_link':
            case 'fetch_social_user':
                $helper::setParam('signup_config', $SYSTEM['config']->getConfig('signup_config', true));
                break;
        }
    }
    if (isset($_GET, $_GET['request'])) {
        switch ($_GET['request']) {
            case 'get_simple_captcha':
            case 'get_svg_captcha':
                $helper::setParam('captcha_config', $SYSTEM['config']->getConfig('captcha_config', true));
                $helper::setParam('simple_captcha', $SYSTEM['config']->getConfig('simple_captcha', true));
                $helper::setParam('svg_captcha', $SYSTEM['config']->getConfig('svg_captcha', true));
                break;
        }
    }
    $result = $helper::fetchResult($method);
}

exit(json_encode($result));
