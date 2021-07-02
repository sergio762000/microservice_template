<?php
use archive\coreapp\Application;
use archive\coreapp\ConfigHandler;
use archive\coreapp\ContentHandler;
use archive\coreapp\Router;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';
defined('WORK_MODE_APP') or define('WORK_MODE_APP', ConfigHandler::getApplicationMode());

if (WORK_MODE_APP == 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
}

$router = new Router();
$router->initRoutes();

$application = new Application($router, ContentHandler::getContentFromRequest());
$response = $application->run();

$application->terminate($response);
