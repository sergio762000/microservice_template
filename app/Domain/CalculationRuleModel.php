<?php


namespace archive\app\Domain;


use archive\app\Infrastructure\CalculationRuleRepository;
use archive\config\PrivilegedTypeSignal;
use archive\coreapp\CalculationRuleListFields;

class CalculationRuleModel
{
    use CalculationRuleListFields;
    use PrivilegedTypeSignal;

    const TYPE_PARAMETER = 0;
    const IS_REQUIRED_PARAMETER = 1;
    const TYPE_NULLABLE = 2;
    const QUANTITY_PARAM_SCHEDULE = 5;

    private $repository;

    /**
     * CalculationRuleModel constructor.
     */
    public function __construct()
    {
        $this->repository = new CalculationRuleRepository();
    }

    public function prepareDataForValidate($content = array()): array
    {
        $dataForValidate = array();

        foreach ($content['data'] as $key => $value) {
            if (!array_key_exists($key, $this->getCRListFields())) {
                unset($content['data'][$key]);
            }
        }

        foreach ($content['data'] as $fieldName => $fieldValue) {
            if ($fieldName != 'calculation_rule_id') {
                $dataForValidate[$fieldName] = $content['data'][$fieldName];
            }
        }

        return $dataForValidate;
    }

    public function validateParameters(&$dataForValidate): array
    {
        $isValid['status'] = true;
        $isValid['error'] = array();
        $arrRequiredFields = array();

        //массив обязательных полей
        foreach ($this->getCRListFields() as $requiredFieldsName => $parameter) {
            if ($requiredFieldsName != 'calculation_rule_id') {
                if ($parameter[self::IS_REQUIRED_PARAMETER] === 'true' && $parameter[self::TYPE_NULLABLE] === 'false') {
                    $arrRequiredFields[] = $requiredFieldsName;
                }
            }
        }

        //проверяем наличие обязательных полей в запросе
        if (!empty($arrRequiredFields)) {
            foreach ($arrRequiredFields as $arrRequiredFieldName) {
                if (!array_key_exists($arrRequiredFieldName, $dataForValidate)) {
                    $isValid['status'] = false;
                    $isValid['error'][] = 'Отсутствует или пуст обязательный параметр: ' . $arrRequiredFieldName;
                }
            }
        }

        //выход, если отсутствует обязательный параметр
        if ($isValid['status'] === false) {
            return $isValid;
        }

        //Если все обязательные параметры в наличии, тогда
        // проверяем, что все параметры нужного типа
        $isValid = $this->validateTypeParameters($dataForValidate);
        if ($isValid['status'] === false) {
            return $isValid;
        }

        return $this->validateContentParameters($dataForValidate);
    }

    public function findCalculationRule($arrQueryString)
    {
        $dataForSearch = array();
        foreach ($arrQueryString as $parameterName => $parameterValue) {
            $dataForSearch['bindValue'][':'.$parameterName] = $parameterValue;
        }

        $resultSearch = $this->repository->findCalculationRule($dataForSearch);

        if ($resultSearch) {
            foreach ($resultSearch as $rule => $ruleContent) {
                $stringWONegativeNumber = str_replace('-1', '*', $ruleContent['calculation_schedule']);
                $stringWOBracket = trim($stringWONegativeNumber, '}{');
                $scheduleArray = explode(',', $stringWOBracket);
                $resultSearch[$rule]['calculation_schedule'] = $scheduleArray;
            }

        }
        return $resultSearch;
    }

    public function save(array $validatedData): array
    {
        $dataForSave['listColumnTable'] = '';
        $dataForSave['aliasColumnTable'] = '';

        foreach ($validatedData as $parameterName => $parameterValue) {
            $dataForSave['listColumnTable'] .= $parameterName . ', ';
            $dataForSave['aliasColumnTable'] .= ':' . $parameterName . ', ';

            if (gettype($parameterValue) == 'array') {
                //преобразовать массив в правильную строку для SQL-запроса
                $arrayToStringForSQLRequest = '{';
                foreach ($parameterValue as $stringValue) {
                    $arrayToStringForSQLRequest .= $stringValue . ',';
                }
                $arrayToStringForSQLRequest = rtrim($arrayToStringForSQLRequest, ',');
                $arrayToStringForSQLRequest .= '}';
                $dataForSave['bindValue'][':'.$parameterName] = $arrayToStringForSQLRequest;
            } else {
                $dataForSave['bindValue'][':'.$parameterName] = $parameterValue;
            }
        }
        $dataForSave['listColumnTable'] = rtrim($dataForSave['listColumnTable'], ' ,');
        $dataForSave['aliasColumnTable'] = rtrim($dataForSave['aliasColumnTable'], ' ,');


        $result = $this->repository->saveCalculationRule($dataForSave);

        if ($result) {
            return array(
                'status' => true,
                'data' => $result
            );
        } else {
            return array(
                'status' => false
            );
        }

    }

    public function deleteCalculationRule(int $id)
    {
        return $this->repository->deleteCalculationRule($id);
    }

    private function validateTypeParameters($dataForValidate): array
    {
        $isValid['status'] = true;
        $isValid['error'] = array();

        foreach ($this->getCRListFields() as $fieldName => $parameter) {
            if (!array_key_exists($fieldName, $dataForValidate)) {
                continue;
            }

            $typeDataForField = gettype($dataForValidate[$fieldName]);
            if ($typeDataForField !== $parameter[self::TYPE_PARAMETER] ) {
                $isValid['status'] = false;
                $isValid['error'][] = 'Для поля: ' . $fieldName . ' ожидается тип данных ' . $parameter[self::TYPE_PARAMETER] . '. По факту - ' . $typeDataForField;
            }
        }

        return $isValid;
    }

    private function validateContentParameters(&$dataForValidate): array
    {
        $isValid['status'] = true;
        $isValid['error'] = array();

        foreach ($dataForValidate as $key => $value) {
            if (gettype($value) == "string") {
                $dataForValidate[$key] = mb_strtolower($value);
            }
        }

        //проверка поля object_type ['bms' || 'complex' || 'building' || 'apartment']
        $objectTypeArray = array('bms', 'complex', 'building', 'apartment');
        if (isset($dataForValidate['object_type'])) {
            $resultSearch = array_search($dataForValidate['object_type'], $objectTypeArray);

            if ($resultSearch === false) {
                $isValid['status'] = false;
                $isValid['error'][] = "Для поля `object_type` ожидается значение из списка {'bms', 'complex', 'building', 'apartment'}";
            }
        }

        if (isset($dataForValidate['object_id'])) {
            if ($dataForValidate['object_id'] < 1 || $dataForValidate['object_id'] > PHP_INT_MAX) {
                $isValid['status'] = false;
                $isValid['error'][] = "Для поля `object_id` ожидается целое значение в диапазоне 1-" . PHP_INT_MAX;
            }
        }

        if (isset($dataForValidate['type_signal'])) {
            $resultOperation = $this->repository->findTypeSignal($dataForValidate['type_signal']);
            if (!$resultOperation) {
                $isValid['status'] = false;
                $isValid['error'][] = "Указанный тип сигнала: {$dataForValidate['type_signal']} - отсутствует в таблице universal_types";
            }
        }

        if (isset($dataForValidate['tags_device']) && !empty($dataForValidate['tags_device'])) {
            //проверка содержимого, удаление "пустых" наименований

            $tagNameList = $this->preliminaryClearListTags($dataForValidate, 'tags_device');

            $resultOperation = $this->repository->findTagsDevice($tagNameList);

            if (!$resultOperation) {
                //если тэги устройств не найдены
                $isValid['status'] = false;
                $isValid['error'][] = "Указанные тэги отсутствует в таблице device_tags";
            } else {
                //сформировать строку, для записи в БД
                $stringTagsDevice = '';
                foreach ($resultOperation as $objectTagDevice) {
                    $stringTagsDevice .= $objectTagDevice->tag_name . ', ';
                }
                $dataForValidate['tags_device'] = rtrim($stringTagsDevice, ' ,');
            }
        } else {
            unset($dataForValidate['tags_device']);
        }

        if (isset($dataForValidate['tags_signal']) && !empty($dataForValidate['tags_signal'])) {
            //проверка содержимого, удаление "пустых" наименований
            $tagNameList = $this->preliminaryClearListTags($dataForValidate, 'tags_signal');

            if (strlen($tagNameList) < 1) {
                if (in_array($dataForValidate['type_signal'], $this->getPrivilegedTypeSignal())) {
                    $isValid['status'] = false;
                    $isValid['error'][] = 'Указанный type_signal обязывает указать tags_signal';
                } else {
                    unset($dataForValidate['tags_signal']);
                }
            } else {
                $resultOperation = $this->repository->findTagsSignal($tagNameList);
                if (!$resultOperation) {
                    //если тэги устройств не найдены
                    $isValid['status'] = false;
                    $isValid['error'][] = "Указанные тэги отсутствует в таблице signal_tags";
                } else {
                    //сформировать строку, для записи в БД
                    $stringTagsDevice = '';
                    foreach ($resultOperation as $objectTagDevice) {
                        $stringTagsDevice .= $objectTagDevice->tag_name . ', ';
                    }
                    $dataForValidate['tags_signal'] = rtrim($stringTagsDevice, ' ,');
                }
            }
        } else {
            if (in_array($dataForValidate['type_signal'], $this->getPrivilegedTypeSignal())) {
                $isValid['status'] = false;
                $isValid['error'][] = 'Указанный type_signal обязывает указать tags_signal';
            } else {
                unset($dataForValidate['tags_signal']);
            }
        }


        if (isset($dataForValidate['time_period']) && !empty($dataForValidate['time_period'])) {
            //проверка time period (1d ... Nd, 1w ... 52w, 1m ... 12m)
            // проверка через регулярку. (первый 1 или 2 символа должны быть числом, последний символ [d|w|m])

            $resultSearchMatch = preg_match("#^(\d?\d)([d|w|m])$#", $dataForValidate['time_period'], $resultSearchTimePeriod);

            //проверка числа в зависимости от буквы
            if ($resultSearchMatch) {
                $resultOperation = $this->checkTimePeriod($resultSearchTimePeriod);
                if ($resultOperation['status'] === false) {
                    $isValid['status'] = false;
                    $isValid['error'][] = "Указанный временной период не совпадает с рекомендуемыми (1d ... 31d, 1w ... 52w, 1m ... 12m)";
                }
            } else {
                $isValid['status'] = false;
                $isValid['error'][] = "Указанный временной период не совпадает с рекомендуемыми (1d ... 31d, 1w ... 52w, 1m ... 12m)";
            }
        } else {
            unset($dataForValidate['time_period']);
        }

        if (isset($dataForValidate['calculation_schedule']) && !empty($dataForValidate['calculation_schedule'])) {

            //проверка параметра "дата и время расчета"
            if (count($dataForValidate['calculation_schedule']) == self::QUANTITY_PARAM_SCHEDULE ) { // их точно пять
                $resultOperation = $this->checkScheduleParameter($dataForValidate['calculation_schedule']);
                if ($resultOperation['status'] == false) {
                    $isValid['status'] = false;
                    $isValid['error'][] = 'Параметры для расписания расчета имеют неподобающий формат';
                } else {
                    $dataForValidate['calculation_schedule'] = $this->starToNegativeInteger($dataForValidate['calculation_schedule']);
                }
            }
        } else {
            $dataForValidate['calculation_schedule'] = array(0, 0, -1, -1, -1);
        }

        return $isValid;
    }

    private function preliminaryClearListTags(&$dataForValidate, string $parameter): string
    {
        $tagNameList = '';
        foreach ($dataForValidate[$parameter] as $key => $tag_name) {
            if (empty($tag_name) || is_numeric($tag_name)) {
                unset($dataForValidate[$parameter][$key]);
            } else {
                $tagNameList .= "'" . $tag_name . "',";
            }
        }

        return rtrim($tagNameList, ', ');
    }

    private function checkTimePeriod($arrTimePeriod): array
    {
        $quantityUnit = 1;
        $measureUnit = 2;
        $dayOfMonthMax = 31;
        $dayOfMonthMin = 1;
        $weekInYearMin = 1;
        $weekInYearMax = 52;
        $monthOfYearMin = 1;
        $monthOfYearMax = 12;
//        $isValid['status'] = true;
        $resultCheck['status'] = true;

        switch (mb_strtolower($arrTimePeriod[$measureUnit])) {
            case "d":
                if ($arrTimePeriod[$quantityUnit] < $dayOfMonthMin || $arrTimePeriod[$quantityUnit] > $dayOfMonthMax) {
                    $resultCheck['status'] = false;
                    $resultCheck['error'][] = 'Количество дней в месяце от ' . $dayOfMonthMin .  ' до ' . $dayOfMonthMax;
                }
                break;
            case "w":
                if ($arrTimePeriod[$quantityUnit] < $weekInYearMin || $arrTimePeriod[$quantityUnit] > $weekInYearMax) {
                    $resultCheck['status'] = false;
                    $resultCheck['error'][] = 'Количество недель в году от ' . $weekInYearMin .  ' до ' . $weekInYearMax;
                }
                break;
            case "m":
                if ($arrTimePeriod[$quantityUnit] < $monthOfYearMin || $arrTimePeriod[$quantityUnit] > $monthOfYearMax) {
                    $resultCheck['status'] = false;
                    $resultCheck['error'][] = 'Количество месяцев в году от ' . $monthOfYearMin .  ' до ' . $monthOfYearMax;
                }
                break;
            default:
                $resultCheck['status'] = false;
                $resultCheck['error'][] = 'Единица измерения периода времени для расчета { d(ay) | w(eek) | m(onth)}';
                break;
        }

        return $resultCheck;
    }

    private function checkScheduleParameter($calculation_schedule): array
    {
        $resultOperation['status'] = true;

        for ($i = 0; $i < self::QUANTITY_PARAM_SCHEDULE; $i++) { // обход параметров с проверкой: "что это?"
            if ($i <> 1) { // если это не часы
                if ($i == 0 && !is_numeric($calculation_schedule[$i])) {
                    $resultOperation['status'] = false;
                    $resultOperation['error'][] = 'Должна быть указана конкретная минута для правила расчета (0-59)';
                }
                if ($i == 2 && (!is_numeric($calculation_schedule[$i]) && $calculation_schedule[$i] != '*')) {
                    $resultOperation['status'] = false;
                    $resultOperation['error'][] = 'День месяца для правила расчета должен быть \'*\' или число (1-31)';
                }
                if ($i == 3 && (!is_numeric($calculation_schedule[$i]) && $calculation_schedule[$i] != "*")) {
                    $resultOperation['status'] = false;
                    $resultOperation['error'][] = 'Месяц года для правила расчета должен быть \'*\' или число (1-12)';
                }
                if ($i == 4 && (!is_numeric($calculation_schedule[$i]) && $calculation_schedule[$i] != "*")) {
                    $resultOperation['status'] = false;
                    $resultOperation['error'][] = 'День недели для правила расчета должен быть \'*\' или число (1-7)';
                }
            } else { //если это - часы, то проверяем, что указан конкретный час
                if (!is_numeric($calculation_schedule[$i]) || (integer)$calculation_schedule[$i] < 0 || (integer)$calculation_schedule[$i] > 23) { // если это не число || строка которую можно привести к числу
                    $resultOperation['status'] = false;
                    $resultOperation['error'][] = 'Должен быть указан конкретный час (0-23) для правила расчета ';
                }
            }
        }

        return $resultOperation;
    }

    // преобразование '*'  в число -1
    private function starToNegativeInteger($calculation_schedule)
    {
        foreach ($calculation_schedule as $key => $value) {
            if ($value === '*') {
                $calculation_schedule[$key] = -1;
            }
        }

        return $calculation_schedule;
    }

}
