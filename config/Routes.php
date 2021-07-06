<?php


namespace microservice_template\config;


trait Routes
{
    public function getRoutes()
    {
        //'{path}'  => array('{path_to_Controller}', {'method_in_Controller'})
        return array(
            '/' => array('microservice_template\app\Controller\CalculationRuleController', 'default'),
            '/index.php' => array('microservice_template\app\Controller\CalculationRuleController', 'default'),
            '/calculation_rule' => array('microservice_template\app\Controller\CalculationRuleController', 'calculationRule'),
            '/calculated_data' => array('microservice_template\app\Controller\CalculatedDataController', 'calculationDeltaValueLastPeriod'),
        );
    }
}
