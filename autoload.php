<?php

use microservice_template\coreapp\AutoloadNamespace;

require_once __DIR__ . '/coreapp/AutoloadNamespace.php';

$autoloader = new AutoloadNamespace();
$autoloader->addNamespace('archive', __DIR__);

$autoloader->register();
