<?php


namespace microservice_template\coreapp;

use Exception;

abstract class ProdBaseConnection
{

    public static function getConnectionToProdBase(): \PDO
    {

        $db_conf = parse_ini_file(__DIR__ . '/../config/database.calculation_rule.conf');
        if (file_exists(__DIR__ . '/../config/database.production.conf')) {
            $db_conf = parse_ini_file(__DIR__ . '/../config/database.production.conf');
        }

        $dsn = $db_conf['type']
            . ":host="      . $db_conf['host']
            . ";port="      . $db_conf['port']
            . ";dbname="    . $db_conf['dbname'];
        $user = $db_conf['username'];
        $password = $db_conf['password'];

        try {
            $connection = new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_PERSISTENT => false
            ]);
        } catch (Exception $exception) {
            PDOLogger::saveMessageToLog($exception);
            $response = json_encode(array(
                'action' => "Подключение к $dsn не удалось, Сущность - CalculationRule",
                'error' => '1',
                'message' => 'Более подробная информация находится в ' . realpath(PDOLogger::PDO_LOG_FILE),
            ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

            header('Content-Type: application/json');
            die($response);
        }

        return $connection;
    }

}

