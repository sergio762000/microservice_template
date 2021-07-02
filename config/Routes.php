<?php


namespace archive\config;


trait Routes
{
    public function getRoutes()
    {
        //'{path}'  => array('{path_to_Controller}', {'method_in_Controller'})
        return array(
            '/' => array('archive\app\Controller\CalculationRuleController', 'default'),
            '/index.php' => array('archive\app\Controller\CalculationRuleController', 'default'),
            '/calculation_rule' => array('archive\app\Controller\CalculationRuleController', 'calculationRule'),
            '/calculated_data' => array('archive\app\Controller\CalculatedDataController', 'calculationDeltaValueLastPeriod'),
        );
    }
}
