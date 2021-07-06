<?php


namespace microservice_template\app\Controller;


use microservice_template\app\Domain\CalculationRuleModel;

class CalculationRuleController
{
    private $contentBR;
    private $model;

    public function __construct($contentBodyRequest)
    {
        $this->contentBR = $contentBodyRequest;
        $this->model = new CalculationRuleModel();
    }

    public function default()
    {
        return json_encode(array(
            'action' => 'Действие по умолчанию',
            'error' => '0',
            'message' => 'Запуск без указания сущности невозможен. Используйте /calculation_rule'
        ));
    }

    public function calculationRule()
    {
        $isValid['status'] = true;

        switch ($this->contentBR['service']['method']) {
            case 'GET':
                if (!empty($this->contentBR['data']['query_string'])) {
                    parse_str($this->contentBR['data']['query_string'], $arrQueryString);
                    $isValid = $this->checkObjectTypeAndId($arrQueryString);
                    if ($isValid['status'] === true) {
                        $resultSearch = $this->model->findCalculationRule($arrQueryString);
                        if ($resultSearch) {
                            $response = $this->answerIfFindCalculationRuleSuccess($arrQueryString, $resultSearch);
                        } else {
                            $response = $this->answerIfNotCalculationRuleForCurrentParameter($arrQueryString);
                        }
                    } else {
                        $response = $this->answerIfCheckObjectTypeAndIdFail($isValid);
                    }
                } else {
                    $response = $this->answerIfNotRequiredParameter();
                }
                break;
            case 'POST':
                $dataForValidate = $this->model->prepareDataForValidate($this->contentBR);
                $isValid = $this->model->validateParameters($dataForValidate);

                if ($isValid['status'] === false) {
                    $response = $this->answerIfValidationFail($isValid);
                } else {
                    $resultOperation = $this->model->save($dataForValidate);
                    if ($resultOperation['status'] === true) {
                        $response = $this->answerIfSaveOperationSuccess($resultOperation);
                    } else {
                        $response = $this->answerIfSaveOperationFail();
                    }
                }
                break;
            case 'DELETE':
                if (isset($this->contentBR['data']['id']) && !empty($this->contentBR['data']['id'])) {
                    $resultOperation = $this->model->deleteCalculationRule((int) $this->contentBR['data']['id']);
                    if ($resultOperation) {
                        $response = $this->answerIfOperationDeleteSuccess((int) $this->contentBR['data']['id']);
                    } else {
                        $response = $this->answerIfOperationDeleteFail((int) $this->contentBR['data']['id']);
                    }
                } else {
                    $response = $this->answerIfIdFail();
                }
                break;
            default:
                $response = $this->answerIfHTTPMethodFail();
                break;
        }

        return $response;
    }

    private function answerIfIdFail()
    {
        return json_encode(array(
            'action' => 'CalculationRule::delete()',
            'error' => '1',
            'message' => 'Идентификатор отсутствует или нулевой'
        ));
    }

    private function answerIfHTTPMethodFail()
    {
        return json_encode(array(
            'action' => 'CalculationRuleController::calculationRule()',
            'error' => '1',
            "message" => 'HTTP метод не определен'
        ));
    }

    private function answerIfOperationDeleteSuccess($idCalculationRule)
    {
        return json_encode(array(
            'action' => 'CalculationRule::delete()',
            'error' => '0',
            'message' => 'Удалена запись с ИД: ' . $idCalculationRule
        ));
    }

    private function answerIfOperationDeleteFail(int $idCalculationRule)
    {
        return json_encode(array(
            'action' => 'CalculationRule::delete()',
            'error' => '1',
            'message' => 'Попытка удалить запись с ИД: ' . $idCalculationRule . ' не удалась. Подробности в log-файле.'
        ));
    }

    private function answerIfValidationFail($isValid)
    {
        return json_encode(array(
            'action' => 'CalculationRule::save(), сущность - CalculationRule',
            'error' => '1',
            'message' => $isValid['error']
        ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    private function answerIfSaveOperationSuccess($resultOperation)
    {
        $linkToRecord = 'http://' . $this->contentBR['service']['http_host'] . $this->contentBR['service']['path_info'] . '?calculation_rule_id=';

        return json_encode(array(
            'action' => 'CalculationRule::save(), сущность - CalculationRule',
            'error' => '0',
            'message' => 'Сохраненная запись доступна по ссылке: ' . $linkToRecord . $resultOperation['data']['lastInsertId']
        ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    private function answerIfSaveOperationFail()
    {
        return json_encode(array(
            'action' => 'CalculationRule::save(), сущность - CalculationRule',
            'error' => '1',
            'message' => 'При сохранении данных в таблицу calculation_rule возникли трудности. Подробности в лог файле'
        ));
    }

    private function answerIfNotRequiredParameter()
    {
        return json_encode(array(
            'action' => 'CalculationRule::find(), сущность - CalculationRule',
            'error' => '1',
            'message' => 'Для нахождения правил расчета укажите object (bmx | complex | building | apartment) и object_id (идентификатор объекта)'
        ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    private function answerIfCheckObjectTypeAndIdFail(array $isValid)
    {
        return json_encode(array(
            'action' => 'CalculationRule::find(), сущность - CalculationRule',
            'error' => '1',
            'message' => $isValid['error']
        ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }

    private function answerIfNotCalculationRuleForCurrentParameter($arrQueryString)
    {
        return json_encode(array(
            'action' => 'CalculationRule::find(object_type=' . $arrQueryString['object_type'] . ', object_id=' . $arrQueryString['object_id'] . ')',
            'error' => '1',
            'message' => 'Поиск по параметрам указанным в запросе не нашел ни одного правила'
        ));
    }

    private function answerIfFindCalculationRuleSuccess($arrQueryString, $resultSearch)
    {
        return json_encode(array(
            'action' => 'CalculationRule::find(object_type=' . $arrQueryString['object_type'] . ', object_id=' . $arrQueryString['object_id'] . ')',
            'error' => '0',
            'data' => $resultSearch
        ));
    }

    private function checkObjectTypeAndId(array $object): array
    {
        $isValid['status'] = true;

        $arrayObjectType = array('bms', 'complex', 'building', 'apartment');

        if (!array_key_exists('object_type', $object) || !array_key_exists('object_id', $object)) {
            $isValid['status'] = false;
            $isValid['error'][] = 'Отсутствует один или оба параметра для поиска calculation_rule';
        } else {
            $resultSearch = array_search($object['object_type'], $arrayObjectType);
            if ($resultSearch === false) {
                $isValid['status'] = false;
                $isValid['error'][] = 'Тип объекта не соответствует ни одному из возможных';
            }

            if (!is_numeric($object['object_id'])) {
                $isValid['status'] = false;
                $isValid['error'][] = 'Идентификатор объекта не распознан как число';
            }
        }

        return $isValid;
    }

}
