<?php


namespace microservice_template\app\Controller;



class DataController
{
    private $contentBR;
    private $model;

    public function __construct($contentBodyRequest)
    {
        $this->contentBR = $contentBodyRequest;
//        $this->model = new DataModel();
    }

    public function calculationDeltaValueLastPeriod()
    {
        switch ($this->contentBR['service']['method']) {
            case 'GET':
                $response = '';
                break;
            default:
                $response = $this->answerIfHTTPMethodFail();
                break;
        }

        return $response;
    }

    private function answerIfHTTPMethodFail()
    {
        return json_encode(array(
            'action' => 'CalculationDataController::calculationDeltaValueLastPeriod()',
            'error' => '1',
            "message" => 'HTTP метод не определен'
        ));
    }

}
