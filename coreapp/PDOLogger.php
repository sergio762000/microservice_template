<?php


namespace microservice_template\coreapp;


abstract class PDOLogger
{
    const PDO_LOG_FILE = __DIR__ . '/../log/PDO.log';

    public static function saveMessageToLog(\Exception $exception)
    {
        $stringForRecord = '[' . date("Y-m-d H:i:s P", time()) . '] - ';
        $stringForRecord .= '[' . $exception->getCode() . ']' . ' - ' . $exception->getMessage();
        $stringForRecord .= PHP_EOL . $exception->getFile() . ', строка - ';
        $stringForRecord .= $exception->getLine() . PHP_EOL;
        $stringForRecord .= PHP_EOL;
        $stringForRecord .= 'Время с начала выполнения скрипта прошло: ' . (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) . ' сек.' . PHP_EOL;
        $stringForRecord .= PHP_EOL;

        self::saveDataToFile($stringForRecord);
    }

    private static function saveDataToFile($stringForRecord)
    {
        if (!file_exists(dirname(self::PDO_LOG_FILE))) {
            mkdir(dirname(self::PDO_LOG_FILE));
        }

        if (!file_exists(self::PDO_LOG_FILE)) {
            touch(self::PDO_LOG_FILE);
        }

        $fh = fopen(self::PDO_LOG_FILE, 'a');
        fputs($fh, $stringForRecord);
        fclose($fh);
    }

}
