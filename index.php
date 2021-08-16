<?php

use microservice_template\coreapp\AppExceptionLogger;
use microservice_template\coreapp\Application;
use microservice_template\coreapp\ConfigHandler;
use microservice_template\coreapp\ContentHandler;
use microservice_template\coreapp\Router;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';
defined('WORK_MODE_APP') or define('WORK_MODE_APP', ConfigHandler::getApplicationMode());

if (WORK_MODE_APP == 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
}

try {
    $router = new Router();
    $router->initRoutes();

    $application = new Application($router, ContentHandler::getContentFromRequest());
    $response = $application->run();

    $application->terminate($response);
} catch (Exception $exception) {
    AppExceptionLogger::saveException($exception);
}

