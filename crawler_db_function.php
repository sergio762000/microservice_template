<?php

function i_closeDatabaseConnection($connection_name): bool
{
    return pg_close($connection_name);
}

function i_connectionToCalculationRule()
{
    $db_conf = parse_ini_file(__DIR__ . '/config/database.calculation_rule.conf');

    $connString = 'host=' . $db_conf['host']
                . " port=" . $db_conf['port']
                . ' dbname=' . $db_conf['dbname']
                . ' user=' . $db_conf['username']
                . ' password=' . $db_conf['password']
                . ' options=\'--client_encoding=UTF8\'';

    $connect = pg_connect($connString, PGSQL_CONNECT_FORCE_NEW);
    if ($connect === false) {
        $message = 'БД calculation_rule недоступна. Проверьте настройки и соединение' . PHP_EOL;
        \archive\coreapp\CrawlerLogger::saveMessage($message);
        die($message);
    }

    return $connect;
}

