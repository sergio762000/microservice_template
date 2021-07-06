<?php
/* локальные, вспомогательные функции */

function d_answerIfTypeSignalNotIdentify($type_signal)
{
    $message = 'Значение type_signal = ' . $type_signal . ' не может быть обработано! Отсутствует бизнес-логика' . PHP_EOL;
    \microservice_template\coreapp\CrawlerLogger::saveMessage($message);
}

function d_answerIfObjectTypeNotIdentify($object_type)
{
    $message = 'Значение типа объекта = ' . $object_type . ' не может быть обработано! Отсутствует бизнес-логика' . PHP_EOL;
    \microservice_template\coreapp\CrawlerLogger::saveMessage($message);
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
