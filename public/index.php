<?php date_default_timezone_set('Europe/Berlin');
exit;
use Core\Facade\App;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

/**
 * Self-called anonymous function that creates its own scope and keep the global namespace clean.
 */
call_user_func(function () {

    App::init(require 'config/config.php');
    App::initSession();
    App::initPipeline();

    App::getInstance()->run();
});
