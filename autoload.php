<?php

use microservice_template\coreapp\AutoloadNamespace;

require_once __DIR__ . '/coreapp/AutoloadNamespace.php';

$autoloader = new AutoloadNamespace();
$autoloader->addNamespace('microservice_template', __DIR__);

$autoloader->register();
