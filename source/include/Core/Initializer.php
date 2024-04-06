<?php
//Handle error
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);

//Check autoload
if (file_exists(dirname(dirname(__DIR__)).'/vendor/autoload.php') === false) {
    echo '<h1>Could not find the autoload file !</h1>',"\n";
    echo '<h2>Please run "composer install" command in the root directory</h2>',"\n";
    exit();
}

//Require autoload
require dirname(dirname(__DIR__)).'/vendor/autoload.php';
