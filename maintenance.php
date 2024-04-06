<?php
require dirname(__FILE__).'/source/include/Core/Initializer.php';

// Get core
$core = new Core\Core;
$SYSTEM = $core->getSystem();
[$base_url, $login, $meta, $conn, $LANG] = $core->getSystemCommon();

// Composer
use System\Load;
use carry0987\ {
    Helper\Helper,
    Helper\Utils,
    Helper\AuthHelper
};

// Load classes
$load = new Load;
$load->loadDBClass(Load::DB_READ);

// Template setting
$options = array(
    'template_dir' => 'theme/maintenance/',
    'css_dir' => 'theme/maintenance/css/',
    'js_dir' => 'theme/maintenance/js/',
    'cache_dir' => 'data/cache/maintenance/'
);
// Get template
$template = $core->setTemplate($options);

// Check maintenance status
if ($SYSTEM['maintenance'] !== true) {
    Utils::redirectURL('./');
}

// AuthHelper
$authHelper = new AuthHelper($conn);
$authHelper->setSystem($SYSTEM);
$authHelper::setParam('check_otp', false);

// Check login method
$maintenance_login = (isset($_GET['key']) && $_GET['key'] === MAINTENANCE_KEY) ? MAINTENANCE_KEY : false;
if ($maintenance_login !== false) {
    // Remove access token and state from session
    $SYSTEM['rememberMe']::clearAuthCookie(SYSTEM_PATH);
    // Check login permit
    if (isset($_POST['submit'])) {
        $authHelper->setDB(Helper::DB_READ, new DataRead($SYSTEM['sanite']));
        $login_result = $authHelper->processLogin($_POST);
        $login_error = $login_result['error'];
        if ($login_error !== false) {
            $login_error = $SYSTEM['i18n']->getLang($login_error);
        } else {
            Utils::redirectURL('./');
        }
    }
}

include($template->loadTemplate('maintenance.html'));
