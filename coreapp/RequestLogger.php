<?php


namespace archive\coreapp;


abstract class RequestLogger
{
    const REQUEST_LOG_FILE = __DIR__ . '/../log/phpInput.log';

    public static function saveRequestToLog($jsonString)
    {
        $stringForRecord = '[' . date("Y-m-d H:i:s P", time()) . '] - ';
        $stringForRecord .= $jsonString;
        $stringForRecord .= PHP_EOL;
        $stringForRecord .= 'Время с начала выполнения скрипта прошло: ' . (microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]) . ' сек.' . PHP_EOL;
        $stringForRecord .= PHP_EOL;

        self::saveDataToFile($stringForRecord);
    }

    private static function saveDataToFile(string $stringForRecord)
    {
        if (!file_exists(dirname(self::REQUEST_LOG_FILE))) {
            mkdir(dirname(self::REQUEST_LOG_FILE));
        }

        if (!file_exists(self::REQUEST_LOG_FILE)) {
            touch(self::REQUEST_LOG_FILE);
        }

        $fh = fopen(self::REQUEST_LOG_FILE, 'a');
        if (filesize(self::REQUEST_LOG_FILE) > 2000000) {
            $archFile = self::REQUEST_LOG_FILE . '_' . time();
            if (!touch($archFile)) {
                fputs($fh, 'Не могу сделать touch для файла: ' . $archFile);
            }
            chmod($archFile, 0755);
            if (!copy(self::REQUEST_LOG_FILE, $archFile)) {
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
