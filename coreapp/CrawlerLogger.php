<?php


namespace microservice_template\coreapp;


abstract class CrawlerLogger
{
    const CRAWLER_LOG_FILE = __DIR__ . '/../log/crawler.log';
    const MAX_SIZE_FILE_IN_BYTES = 2000000;

    public static function saveMessageAboutActionCalcRuleToLog($rule_id, $action, $result)
    {
        $stringForRecord = '[' . date("Y-m-d H:i:s P", time()) . '] - ';
        $stringForRecord .= '[ ' . mb_strtoupper($result) . ' ]:' . 'Операция: ' . $action . ' при работе с calculation_rule = ' . $rule_id . PHP_EOL;

        self::saveDataToFile($stringForRecord);
    }

    public static function saveMessageFinishWorkToLog($start_script)
    {
        $stringForRecord = '[' . date("Y-m-d H:i:s P", time()) . '] - ';
        $stringForRecord .= 'Время работы скрипта = ' . (microtime(true) - $start_script) . PHP_EOL;
        $stringForRecord .= '|-------------------------------------------------------------------' . PHP_EOL;

        self::saveDataToFile($stringForRecord);
    }

    public static function saveMessage($message)
    {
        $stringForRecord = '[' . date("Y-m-d H:i:s P", time()) . '] - ';
        $stringForRecord .= $message;
        $stringForRecord .= '|-------------------------------------------------------------------' . PHP_EOL;

        self::saveDataToFile($stringForRecord);
    }

    private static function saveDataToFile($stringForRecord)
    {
        if (!file_exists(dirname(self::CRAWLER_LOG_FILE))) {
            mkdir(dirname(self::CRAWLER_LOG_FILE));
        }
        if (!file_exists(self::CRAWLER_LOG_FILE)) {
            touch(self::CRAWLER_LOG_FILE);
        }

        $fh = fopen(self::CRAWLER_LOG_FILE, 'a');
        if (filesize(self::CRAWLER_LOG_FILE) > self::MAX_SIZE_FILE_IN_BYTES) {
            $archFile = self::CRAWLER_LOG_FILE . '_' . time();
            if (!touch($archFile)) {
                fputs($fh, 'Не могу сделать touch для файла: ' . $archFile);
            }
            chmod($archFile, 0755);
            if (!copy(self::CRAWLER_LOG_FILE, $archFile)) {
                $stringForRecordAlarm = 'Не могу скопировать в файл: ' . $archFile;
                fputs($fh, $stringForRecordAlarm);
            } else {
                ftruncate($fh, 0);
                rewind($fh);
                fputs($fh, '=====================================');
                fputs($fh, 'Предыдущий файл - ' . $archFile);
                fputs($fh, '=====================================');
            }
        }

        fputs($fh, $stringForRecord);
        fclose($fh);
    }
}
