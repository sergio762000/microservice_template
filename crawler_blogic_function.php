<?php

function d_executeCalculationRule($parameterSpecificRule, $manualParameters)
{
    $resultExecute = false;
    $isActualId = i_checkIsActualId($parameterSpecificRule['object_id'], $parameterSpecificRule['object_type']);

    if ($isActualId) {
        $dataSpecificCalculationRule['device_tags'] = d_getArrayTagsNameFromString($parameterSpecificRule['tags_device']);
        $dataSpecificCalculationRule['signal_tags'] = d_getArrayTagsNameFromString($parameterSpecificRule['tags_signal']);

        switch ($parameterSpecificRule['object_type']) {
            case 'apartment':
                switch ($parameterSpecificRule['type_signal']) {
                    case 'quantity_device':
                        $resultExecute = i_findQuantityDeviceForApartment(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'quantity_device_fail':
                        $resultExecute = i_findQuantityDeviceFailForApartment(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'no_data_available':
                        $parameterSpecificRule['startTimeForCalculationRule'] = d_getStartTimeForCalculationRule($parameterSpecificRule['time_period'], $manualParameters['timestamp']);
                        $resultExecute = i_findQuantityDeviceWithNoDataAvailableForApartment(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'last_value':
                        $listSignalForApartment = i_findSignalsForApartment(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForApartment)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForApartment);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findLastValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'avg_value':
                        $listSignalForApartment = i_findSignalsForApartment(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForApartment)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForApartment);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findAvgValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'max_value':
                        $listSignalForApartment = i_findSignalsForApartment(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForApartment)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForApartment);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findMaxValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'min_value':
                        $listSignalForApartment = i_findSignalsForApartment(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForApartment)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForApartment);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findMinValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    default:
                        d_answerIfTypeSignalNotIdentify($parameterSpecificRule['type_signal']);
                        break;
                }
                break;
            case 'building':
                switch ($parameterSpecificRule['type_signal']) {
                    case 'quantity_device':
                        $resultExecute = i_findQuantityDeviceForBuilding(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'quantity_device_fail':
                        $resultExecute = i_findQuantityDeviceFailForBuilding(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'no_data_available':
                        $parameterSpecificRule['startTimeForCalculationRule'] = d_getStartTimeForCalculationRule($parameterSpecificRule['time_period'], $manualParameters['timestamp']);
                        $resultExecute = i_findQuantityDeviceWithNoDataAvailableForBuilding(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'last_value':
                        $listSignalForBuilding = i_findSignalsForBuilding(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForBuilding)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForBuilding);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findLastValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'avg_value':
                        $listSignalForBuilding = i_findSignalsForBuilding(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForBuilding)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForBuilding);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findAvgValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'max_value':
                        $listSignalForBuilding = i_findSignalsForBuilding(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForBuilding)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForBuilding);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findMaxValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'min_value':
                        $listSignalForBuilding = i_findSignalsForBuilding(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForBuilding)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForBuilding);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findMinValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    default:
                        d_answerIfTypeSignalNotIdentify($parameterSpecificRule['type_signal']);
                        break;
                }
                break;
            case 'complex':
                switch ($parameterSpecificRule['type_signal']) {
                    case 'quantity_device':
                        $resultExecute = i_findQuantityDeviceForComplex(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'quantity_device_fail':
                        $resultExecute = i_findQuantityDeviceFailForComplex(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'no_data_available':
                        $parameterSpecificRule['startTimeForCalculationRule'] = d_getStartTimeForCalculationRule($parameterSpecificRule['time_period'], $manualParameters['timestamp']);
                        $resultExecute = i_findQuantityDeviceWithNoDataAvailableForComplex(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'last_value':
                        $listSignalForComplex = i_findSignalsForComplex(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForComplex)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForComplex);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findLastValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'avg_value':
                        $listSignalForComplex = i_findSignalsForComplex(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForComplex)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForComplex);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findAvgValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'max_value':
                        $listSignalForComplex = i_findSignalsForComplex(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForComplex)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForComplex);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findMaxValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'min_value':
                        $listSignalForComplex = i_findSignalsForComplex(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForComplex)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForComplex);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findMinValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    default:
                        d_answerIfTypeSignalNotIdentify($parameterSpecificRule['type_signal']);
                        break;
                }
                break;
            case 'bms':
                switch ($parameterSpecificRule['type_signal']) {
                    case 'quantity_device':
                        $resultExecute = i_findQuantityDeviceForBMS(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'quantity_device_fail':
                        $resultExecute = i_findQuantityDeviceFailForBMS(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'no_data_available':
                        $parameterSpecificRule['startTimeForCalculationRule'] = d_getStartTimeForCalculationRule($parameterSpecificRule['time_period'], $manualParameters['timestamp']);
                        $resultExecute = i_findQuantityDeviceWithNoDataAvailableForBMS(
                            $parameterSpecificRule,
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        break;
                    case 'last_value':
                        $listSignalForBMS = i_findSignalsForBMS(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForBMS)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForBMS);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findLastValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'avg_value':
                        $listSignalForBMS = i_findSignalsForBMS(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForBMS)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForBMS);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findAvgValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'max_value':
                        $listSignalForBMS = i_findSignalsForBMS(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForBMS)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForBMS);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findMaxValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    case 'min_value':
                        $listSignalForBMS = i_findSignalsForBMS(
                            $parameterSpecificRule['object_id'],
                            $dataSpecificCalculationRule['device_tags'],
                            $dataSpecificCalculationRule['signal_tags']
                        );
                        if (!empty($listSignalForBMS)) {
                            $parameterSpecificRule['string_signal_id'] = d_getStringSignalIds($listSignalForBMS);
                            $parameterSpecificRule['date'] = d_getDateForSearchLastValue($manualParameters);
                            $resultExecute = i_findMinValueSignalForObject($parameterSpecificRule);
                        }
                        break;
                    default:
                        d_answerIfTypeSignalNotIdentify($parameterSpecificRule['type_signal']);
                        break;
                }
                break;
            default:
                d_answerIfObjectTypeNotIdentify($parameterSpecificRule['object_type']);
                break;
        }
    }

    return $resultExecute;
}

function d_findCalculationRuleAll($parameter): array
{
    $dataForSearch['date'] = getdate($parameter['timestamp']);

    return i_findCalculationRuleCurrentTime($dataForSearch);
}

function d_findCalculationRuleSpecific($parameter): array
{
    return i_findCalculationRuleSpecific($parameter);
}

function d_findCalculationRuleWithStatusInProcess()
{
    $result = false;

    $listCalculationRule = i_findCalculationRuleWithStatusInProcess();
    if (!empty($listCalculationRule)) {
        $result = $listCalculationRule;
    }

    return $result;
}

function d_saveResultInArchive($resultExecCalcRule, $parameterSpecificRule, $parameter)
{
    $y_m_d = getdate($parameter['timestamp'] - 86400);

    return i_saveResultInArchive($resultExecCalcRule, $parameterSpecificRule, $y_m_d);
}

function d_updateFieldStatusForSpecificCalculationRule($calculation_rule_id): bool
{
    return i_updateFieldStatusForSpecificCalculationRule($calculation_rule_id);
}

function d_updateCalcRuleLastNextDate($parametersRule, $parameterTime)
{
    $nextCalculatedDate = d_getNextCalculatedDate($parametersRule['calculation_schedule'], $parameterTime['timestamp']);

    //рассчитать next_calculated_date через calculation_schedule
    $newParameters['calculation_rule_id'] = $parametersRule['calculation_rule_id'];
    $newParameters['last_calculated_date'] = time();
    $newParameters['next_calculated_date'] = $nextCalculatedDate;

    return i_updateCalcRuleLastNextDate($newParameters);
}

/* локальные, вспомогательные функции */

function d_answerIfTypeSignalNotIdentify($type_signal)
{
    $message = 'Значение type_signal = ' . $type_signal . ' не может быть обработано! Отсутствует бизнес-логика' . PHP_EOL;
    \archive\coreapp\CrawlerLogger::saveMessage($message);
}

function d_answerIfObjectTypeNotIdentify($object_type)
{
    $message = 'Значение типа объекта = ' . $object_type . ' не может быть обработано! Отсутствует бизнес-логика' . PHP_EOL;
    \archive\coreapp\CrawlerLogger::saveMessage($message);
}

function d_checkParameterDay(string $date)
{
    $dayOfCalculation = [];

    $arrDate = explode('/', $date);
    $dayOfCalculation['day'] = $day = (int) $arrDate[0];
    $dayOfCalculation['month'] = $month = (int) $arrDate[1];
    $dayOfCalculation['year'] = $year = (int) $arrDate[2];

    //проверка даты на валидность
    if (checkdate($month, $day, $year)) {
        $dayOfCalculation['timestamp'] = mktime(0, 0, 0, $month, $day, $year);

        if ($dayOfCalculation['timestamp'] >= time()) {
            $dayOfCalculation = false;
        }
    }

    return $dayOfCalculation;
}

function d_getArrayTagsNameFromString($tags_name): array
{
    $dataSpecificCalculationRule['device_tags'] = array();

    if (isset($tags_name) && !empty($tags_name)) {
        $listTagsName = explode(',', $tags_name);
        if (count($listTagsName) > 1) {
            foreach ($listTagsName as $tagName) {
                $dataSpecificCalculationRule['device_tags'][] = trim($tagName);
            }
        } else {
            $dataSpecificCalculationRule['device_tags'][] = array_shift($listTagsName);
        }
    }

    return $dataSpecificCalculationRule['device_tags'];
}

function d_getDateForSearchLastValue($manualParameters): array
{
    if ($manualParameters != 0) {
        //Если скрипт запущен вручную, тогда для расчета применяем дату указанную при запуске скрипта
        $parameterSpecificRule['date'] = getdate($manualParameters['timestamp']);
    } else {
        //Иначе для расчета необходимо использовать таблицу за предыдущий день ???
        //todo - так ли это как указано выше?
        $parameterSpecificRule['date'] = getdate(intval($manualParameters['timestamp']) - 86400);
    }

    return $parameterSpecificRule['date'];
}

function d_getNextCalculatedDate($calculation_schedule, $timestamp): int
{
    defined('HOUR_ARRAY_ELEMENT') or define('HOUR_ARRAY_ELEMENT', 1);
    defined('MINUTE_ARRAY_ELEMENT') or define('MINUTE_ARRAY_ELEMENT', 0);
    defined('MDAY_ARRAY_ELEMENT') or define('MDAY_ARRAY_ELEMENT', 2);
    defined('MON_ARRAY_ELEMENT') or define('MON_ARRAY_ELEMENT', 3);
    defined('WDAY_ARRAY_ELEMENT') or define('WDAY_ARRAY_ELEMENT', 4);

    $nextTime = array();

    $arrayCurrentTime = getdate($timestamp);
    $arraySchedule = explode(',', trim($calculation_schedule, '}{'));

    $nextTime['hour'] = (int) $arraySchedule[HOUR_ARRAY_ELEMENT];

    if (!is_numeric($arraySchedule[MINUTE_ARRAY_ELEMENT])) {
        $nextTime['minute'] = 0;
    } else {
        $nextTime['minute'] = (int) $arraySchedule[MINUTE_ARRAY_ELEMENT];
    }

    $nextTime['seconds'] = 0;

    if ($arraySchedule[MDAY_ARRAY_ELEMENT] == -1) {
        if ($arraySchedule[WDAY_ARRAY_ELEMENT] == -1) {
            $nextTime['day'] = (int) $arrayCurrentTime['mday'] + 1;
        } else {
            if ($arrayCurrentTime['wday'] < $arraySchedule[WDAY_ARRAY_ELEMENT]) {
                $nextTime['day'] = (int) $arrayCurrentTime['mday'] + abs($arraySchedule[WDAY_ARRAY_ELEMENT] - $arrayCurrentTime['wday']);
            } else {
                $nextTime['day'] = (int) $arrayCurrentTime['mday'] + abs($arraySchedule[WDAY_ARRAY_ELEMENT] - $arrayCurrentTime['wday'] + 7);
            }
        }
    } else {
        $nextTime['day'] = (int) $arraySchedule[MDAY_ARRAY_ELEMENT];
    }

    if ($arraySchedule[MON_ARRAY_ELEMENT] == -1) {
        $nextTime['mon'] = (int) $arrayCurrentTime['mon'];
    } else {
        $nextTime['mon'] = (int) $arraySchedule[MON_ARRAY_ELEMENT];
    }

    if ($nextTime['mon'] < $arrayCurrentTime['mon']) {
        $nextTime['year'] = (int) $arrayCurrentTime['year'] + 1;
    } else {
        $nextTime['year'] = (int) $arrayCurrentTime['year'];
    }

    return mktime($nextTime['hour'], $nextTime['minute'], $nextTime['seconds'], $nextTime['mon'], $nextTime['day'], $nextTime['year']);

}

function d_getStartTimeForCalculationRule($time_period, $timestamp): int
{
    $day = i_getQuanDaysFromTimePeriod($timestamp, $time_period);

    $secondsInDay =  60*60*24;
    $currentDate = getdate(time());

    return mktime(0,0,0, $currentDate['mon'], $currentDate['mday'], $currentDate['year']) - ($secondsInDay * $day);
}

function d_getStringSignalIds($listSignalForApartment): string
{
    $listSignalIDs = '(';
    foreach ($listSignalForApartment as $listSignalId) {
        $listSignalIDs .= $listSignalId['signal_id'] . ', ';
    }
    $listSignalIDs = rtrim($listSignalIDs, ' ,');
    $listSignalIDs .= ')';

    return $listSignalIDs;
}
