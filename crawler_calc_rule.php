#!/usr/bin/php
<?php

$start_script = microtime(true);
require_once __DIR__ . '/coreapp/CrawlerLogger.php';
use microservice_template\coreapp\CrawlerLogger;

$parameter['timestamp'] = time() ;
$parameter['manual_rule_id'] = 0;
$dataSpecificCalculationRule = false;

require_once __DIR__ . '/crawler_blogic_function.php';
require_once __DIR__ . '/crawler_db_function.php';

$work_mode = parse_ini_file(__DIR__ . '/config/application.conf');
if ($work_mode['work_mode_app'] == 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', true);
} else {
    error_reporting(E_ERROR);
    ini_set('display_errors', false);
}
defined('WORK_MODE') or define('WORK_MODE', $work_mode['work_mode_app']);
defined('CONTINUOUS_OPERATION_TIME_SEC') or define('CONTINUOUS_OPERATION_TIME_SEC', 570);

if ($argc > 1) {
    if (in_array('-h', $argv) || in_array('--help', $argv)) {
        answerIfParameterHelpOrNotSet();
    }
    if (in_array('-p', $argv) || in_array('--process', $argv)) {
        //выбрать все calculation_rule со статусом 'in_process_calculation...'
        $result = d_findCalculationRuleWithStatusInProcess();
        if ($result === false) {
            answerIfNotSearchedCalculationRuleWithStatusProcess();
        } else {
            answerIfSearchedCalculationRuleWithStatusProcess($result);
        }
    }

    if (isset($argv[1]) && !empty($argv[1])) {
        $dayOfCalculation = d_checkParameterDay($argv[1]);
        if (!$dayOfCalculation) {
            answerIfParameterDateIsFail();
        }
        $parameter['date'] = ($dayOfCalculation) ? : $parameter['timestamp'];
    }

    if (isset($argv[2]) && !empty($argv[2]) && is_numeric($argv[2])) {
        $parameter['manual_rule_id'] = (int)$argv[2];
    } else {
        answerIfParameterRuleIdFail();
    }

    $dataSpecificCalculationRule = d_findCalculationRuleSpecific($parameter);

    if (empty($dataSpecificCalculationRule)) {
        answerIfNotSearchedCalculationRuleWithId($parameter['manual_rule_id']);
    }
} else {
    $dataSpecificCalculationRule = d_findCalculationRuleAll($parameter);
    if (empty($dataSpecificCalculationRule)) {
        answerIfNotRulesForCalculation();
    }
}

foreach ($dataSpecificCalculationRule as $key => $parameterSpecificRule) {
    //перед отправкой правила обновляем calculation_rule_status -> 'в процессе расчета'
    $resultUpdateCalcRuleStatus = d_updateFieldStatusForSpecificCalculationRule($parameterSpecificRule['calculation_rule_id']);

    //получаем назад информацию о результате процесса обсчета правила
    $resultExecuteCalculationRule = d_executeCalculationRule($parameterSpecificRule, $parameter);

    //сохранение полученного значения в нужном архиве
    if ($resultExecuteCalculationRule !== false) {
        CrawlerLogger::saveMessageAboutActionCalcRuleToLog($parameterSpecificRule['calculation_rule_id'], 'd_executeCalculationRule()', 'success');
        $resultSaveInArchive = d_saveResultInArchive($resultExecuteCalculationRule, $parameterSpecificRule, $parameter);
    } else {
        $resultSaveInArchive = false;
        CrawlerLogger::saveMessageAboutActionCalcRuleToLog($parameterSpecificRule['calculation_rule_id'], 'd_executeCalculationRule()', 'fail');
    }

    //если обсчет и сохранение результатов произошел успешно, то изменяем данные по текущему правилу:
    //обновляем поля last_calculated_date (текущим временем) и next_calculated_date
    // (рассчитывается по содержимому поля calculation_schedule)
    // обновляем calculation_rule_status -> 'waiting calculation...'
    if ($resultSaveInArchive !== false) {
        CrawlerLogger::saveMessageAboutActionCalcRuleToLog($parameterSpecificRule['calculation_rule_id'], 'd_saveResultInArchive()', 'success');
        $resultUpdateCalcRuleLastNextDate = d_updateCalcRuleLastNextDate($parameterSpecificRule, $parameter);
    } else {
        CrawlerLogger::saveMessageAboutActionCalcRuleToLog($parameterSpecificRule['calculation_rule_id'], 'd_saveResultInArchive()', 'fail');
    }

    $timeWorkingScript = microtime(true) - $start_script;
    if ($timeWorkingScript > CONTINUOUS_OPERATION_TIME_SEC) {
        die('Время работы скрипта превышено. Остановка ...' . $timeWorkingScript . PHP_EOL);
    }

}

if (WORK_MODE == 'dev') {
    echo 'Время работы скрипта = ' . (microtime(true) - $start_script) . PHP_EOL;
    echo 'Работа обходчика завершена!!!' . PHP_EOL;
} else {
    CrawlerLogger::saveMessageFinishWorkToLog($start_script);
}

function answerIfParameterHelpOrNotSet()
{
    echo 'Для запуска скрипта используйте следующий формат:' . PHP_EOL;
    echo 'crawler_cal_rule [-h | --help] | [dd/mm/yyyy id_rule] | [-p | --process]' . PHP_EOL;
    echo PHP_EOL;
    echo "\t" . '-h | --help' . "\t" . ':вывод текущей справки' . PHP_EOL;
    echo "\t" . 'dd/mm/yyyy' . "\t" . ':день/месяц/год за который произвести расчет' . PHP_EOL;
    echo "\t" . 'rule_id' . "\t\t" . ':идентификатор правила для расчета (numeric)' . PHP_EOL;
    echo "\t" . '-p | --process' . "\t" . ':вывод правил в состоянии \'in_process_calculation...\'' . PHP_EOL;

    die();
}

function answerIfParameterRuleIdFail()
{
    $message = 'Параметр обозначающий номер правила - не установлен/пуст/не число!!!' . PHP_EOL;

    CrawlerLogger::saveMessage($message);
    die($message);
}

function answerIfParameterDateIsFail()
{
    $message = 'Дата указанная для выполнения правила не должна быть больше текущего дня...' . PHP_EOL;
    $message .= 'Для расчета будет применена текущая дата...' . PHP_EOL;

    CrawlerLogger::saveMessage($message);
    die($message);
}

function answerIfSearchedCalculationRuleWithStatusProcess($listCalculationRule)
{
    $resultString = 'Список правил расчета, находящихся в состоянии \'in_process_calculation...\' по состоянию на ' . date('Y-m-d H:i:s') . PHP_EOL;
    $resultString .= '|------------------------------------------------------------------------------------------------------------|' . PHP_EOL;
    $resultString .= '| rule_id | object_type | object_id |  last_updated_time  | last_calculated_date |          rule_name        ' . '|' . PHP_EOL;
    $resultString .= '|------------------------------------------------------------------------------------------------------------|' . PHP_EOL;

    foreach ($listCalculationRule as $calculationRule) {
        $resultString .= sprintf("|%' 8d | %' 11s | %' 9s | %' 17s | %' 20s | %-' 25s |\n",
            $calculationRule['calculation_rule_id'],
            $calculationRule['object_type'],
            $calculationRule['object_id'],
            date('Y-m-d H:i:s', $calculationRule['calculation_rule_status_ut']),
            date('Y-m-d H:i:s', $calculationRule['last_calculated_date']),
            $calculationRule['calculation_rule_name']);
    }

    $resultString .= '|------------------------------------------------------------------------------------------------------------|' . PHP_EOL;

    die($resultString);
}

function answerIfNotSearchedCalculationRuleWithId($manual_rule_id)
{
    $message = 'Правила с указанным идентификатором: ' . $manual_rule_id . ', не найдено!' . PHP_EOL;
    CrawlerLogger::saveMessage($message);
    die($message);
}

function answerIfNotSearchedCalculationRuleWithStatusProcess()
{
    $currTime = date('Y-m-d H:i:s');
    $message = 'Правил расчета со статусом \'in_process_calculation...\' на ' . $currTime . ' в БД не обнаружено' . PHP_EOL;
    CrawlerLogger::saveMessage($message);
    die($message);
}

function answerIfNotRulesForCalculation()
{
    $currTime = date('Y-m-d H:i:s');
    $message = 'Не найдено правил подлежащих расчету на текущее время ' . $currTime . PHP_EOL;
    CrawlerLogger::saveMessage($message);
    die($message);
}
