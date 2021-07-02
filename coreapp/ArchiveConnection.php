<?php


namespace archive\coreapp;


abstract class ArchiveConnection
{

    public static function getConnectionToArchiveDB(): \PDO
    {
        if (file_exists(__DIR__ . '/../config/database.archive.conf')) {
            $db_conf = parse_ini_file(__DIR__ . '/../config/database.archive.conf');
        } else {
            $db_conf = parse_ini_file(__DIR__ . '/../config/database.calculation_rule.conf');
        }

        $dsn = $db_conf['type']
            . ":host="      . $db_conf['host']
            . ";port="      . $db_conf['port']
            . ";dbname="    . $db_conf['dbname'];
        $user = $db_conf['username'];
        $password = $db_conf['password'];

        try {
            $connection = new \PDO($dsn, $user, $password, array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_PERSISTENT => false
            ));
        } catch (\Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
            $response = json_encode(array(
                'action' => "Подключение к $dsn не удалось, Сущность - CalculationData",
                'error' => '1',
                'message' => 'Более подробная информация находится в ' . realpath(PDOLogger::PDO_LOG_FILE),
            ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

            header('Content-Type: application/json');
            die($response);
        }

        return $connection;
    }
}
