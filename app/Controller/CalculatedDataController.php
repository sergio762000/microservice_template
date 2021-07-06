<?php


namespace microservice_template\app\Controller;


use microservice_template\app\Domain\CalculatedDataModel;

class CalculatedDataController
{
    private $contentBR;
    private $model;

    public function __construct($contentBodyRequest)
    {
        $this->contentBR = $contentBodyRequest;
        $this->model = new CalculatedDataModel();
    }

    public function calculationDeltaValueLastPeriod()
    {
        switch ($this->contentBR['service']['method']) {
            case 'GET':
                if (!empty($this->contentBR['data']['query_string'])) {
                    parse_str($this->contentBR['data']['query_string'], $arrQueryString);
                    $isValid = $this->checkRuleIdAndTypeSignal($arrQueryString);
                    if ($isValid['status'] === true) {
                        $resultSearch = $this->model->searchCalculated($arrQueryString);
                        if ($resultSearch['status'] !== false) {
                            $response = $this->answerIfSearchDataSuccess($arrQueryString, $resultSearch);
                        } else {
                            $response = $this->answerIfSearchDataFail($resultSearch);
                        }
                    } else {
                        $response = $this->answerIfParameterNotValid($isValid);
                    }
                } else {
                    $response = $this->answerIfStringParameterEmpty();
                }
                break;
            default:
                $response = $this->answerIfHTTPMethodFail();
                break;
        }

        return $response;
    }

    private function checkRuleIdAndTypeSignal($arrQueryString): array
    {
        $isValid['status'] = true;

        if (!array_key_exists('rule_id', $arrQueryString)) {
            $isValid['status'] = false;
            $isValid['error'][] = 'Отсутствует обязательный параметр rule_id';
        } else {
            if (!is_numeric($arrQueryString['rule_id'])) {
                $isValid['status'] = false;
                $isValid['error'][] = 'Значение обязательного параметра rule_id должно быть числом';
            }
        }

        if (!array_key_exists('type_signal', $arrQueryString)) {
            $isValid['status'] = false;
            $isValid['error'][] = 'Отсутствует обязательный параметр type_signal';
        } else {
            if (!is_string($arrQueryString['type_signal']) || $arrQueryString['type_signal'] !== 'delta_vlp') {
                $isValid['status'] = false;
                $isValid['error'][] = 'Значение обязательного параметра type_signal должно быть строкой delta_vlp';
            }
        }

        return $isValid;
    }

    private function answerIfParameterNotValid($isValid)
    {
        return json_encode([
            'action' => 'CalculatedData::delta_vlp',
            'error' => '1',
            'message' => $isValid['error']
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    private function answerIfSearchDataSuccess($arrQueryString, $resultSearch)
    {
        return json_encode([
            'action' => 'CalculatedData::delta_vlp',
            'error' => '0',
            'data' => $resultSearch['data']
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    private function answerIfStringParameterEmpty()
    {
        return json_encode([
            'action' => 'CalculatedData::delta_vlp',
            'error' => '1',
            'message' => 'Запуск без параметров невозможен.'
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    private function answerIfSearchDataFail($resultSearch)
    {
        return json_encode([
            'action' => 'CalculatedData::delta_vlp',
            'error' => $resultSearch['error'],
            'message' => $resultSearch['message']
        ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
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
