<?php


namespace microservice_template\config;


trait Routes
{
    public function getRoutes()
    {
        //'{path}'  => array('{path_to_Controller}', {'method_in_Controller'})
        return array(
            '/' => array('microservice_template\app\Controller\DefaultController', 'default'),
            '/index.php' => array('microservice_template\app\Controller\DefaultController', 'default'),
        );
    }
}
