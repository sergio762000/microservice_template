<?php

namespace microservice_template\coreapp;

abstract class AppExceptionLogger
{
    const EXCEPTION_LOG_FILE = __DIR__. '/../log/AppException.log';
    const MAX_SIZE_FILE_IN_BYTES = 2000000;

    public static function saveException(\Exception $exception)
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

    private static function saveDataToFile(string $stringForRecord)
    {
        if (!file_exists(dirname(self::EXCEPTION_LOG_FILE))) {
            mkdir(dirname(self::EXCEPTION_LOG_FILE));
        }

        if (!file_exists(self::EXCEPTION_LOG_FILE)) {
            touch(self::EXCEPTION_LOG_FILE);
            chmod(self::EXCEPTION_LOG_FILE, 0766);
        }

        $fh = fopen(self::EXCEPTION_LOG_FILE, 'a');
        fputs($fh, $stringForRecord);
        fclose($fh);

        clearstatcache(true, self::EXCEPTION_LOG_FILE);
        if ((filesize(self::EXCEPTION_LOG_FILE) - self::MAX_SIZE_FILE_IN_BYTES) > 0) {
            $fh = fopen(self::EXCEPTION_LOG_FILE, 'a+');
            fwrite($fh, 'Размер файла AppException.log превысил максимальный размер: 2000000 байт'. PHP_EOL);
            fwrite($fh, 'Запущена процедура архивирования файла'. PHP_EOL);
            fclose($fh);
            self::createArchiveFile();
        }
    }

    private static function createArchiveFile()
    {
        $archFile = self::EXCEPTION_LOG_FILE . '_' . date('Y_m_d-His', time());
        if (touch($archFile) == true) {
            if (chmod($archFile, 0766) == true) {
                if (copy(self::EXCEPTION_LOG_FILE, $archFile) == true) {
                    $fh = fopen(self::EXCEPTION_LOG_FILE, 'w');
                    fwrite($fh, '=====================================' . PHP_EOL);
                    fwrite($fh, 'Предыдущий файл - ' . $archFile . PHP_EOL);
                    fwrite($fh, '=====================================' . PHP_EOL);
                    fclose($fh);
                } else {
                    $fh = fopen(self::EXCEPTION_LOG_FILE, 'a+');
                    $stringForRecordAlarm = 'Не могу скопировать в файл: ' . $archFile . PHP_EOL;
                    fwrite($fh, $stringForRecordAlarm);
                    fclose($fh);
                }
            } else {
                $stringForRecordAlarm = 'Не могу установить доступ в 0766 для - ' . $archFile . PHP_EOL;
                $fh = fopen(self::EXCEPTION_LOG_FILE, 'a+');
                fwrite($fh, $stringForRecordAlarm);
                fclose($fh);
            }
        } else {
            $fh = fopen(self::EXCEPTION_LOG_FILE, 'a+');
            fwrite($fh, 'Не могу сделать touch для файла: ' . $archFile . PHP_EOL);
            fclose($fh);
        }
    }

}
