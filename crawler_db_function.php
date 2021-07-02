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

function i_connectionToDBProduction()
{
    //если файл database.production.conf отсутствует
    // используем database.calculation_rule.conf
    if (!is_file(__DIR__ . '/config/database.production.conf')) {
        $connect = i_connectionToCalculationRule();
    } else {
        $db_conf = parse_ini_file(__DIR__ . '/config/database.production.conf');

        $connString = 'host=' . $db_conf['host']
            . " port=" . $db_conf['port']
            . ' dbname=' . $db_conf['dbname']
            . ' user=' . $db_conf['username']
            . ' password=' . $db_conf['password']
            . ' options=\'--client_encoding=UTF8\'';

        $connect = pg_connect($connString, PGSQL_CONNECT_FORCE_NEW);
        if ($connect === false) {
            $message = 'БД оборудования недоступна. Проверьте настройки и соединение' . PHP_EOL;
            \archive\coreapp\CrawlerLogger::saveMessage($message);
            die($message);
        }
    }

    return $connect;
}

function i_connectionToDBArchive()
{
    if (!is_file(__DIR__ . '/config/database.archive.conf')) {
        $connect = i_connectionToCalculationRule();
    } else {
        $db_conf = parse_ini_file(__DIR__ . '/config/database.archive.conf');

        $connString = 'host=' . $db_conf['host']
            . " port=" . $db_conf['port']
            . ' dbname=' . $db_conf['dbname']
            . ' user=' . $db_conf['username']
            . ' password=' . $db_conf['password']
            . ' options=\'--client_encoding=UTF8\'';

        $connect = pg_connect($connString, PGSQL_CONNECT_FORCE_NEW);
        if ($connect === false) {
            $message = 'БД архива сигналов недоступна. Проверьте настройки и соединение' . PHP_EOL;
            \archive\coreapp\CrawlerLogger::saveMessage($message);
            die($message);
        }
    }

    return $connect;
}

function i_checkIsActualId($object_id, $object_type)
{
    $resultCheck = false;

    $queryForCheck = 'select * from ' . $object_type . ' where ' . $object_type . '_id = $1';
    $db_conn = i_connectionToDBProduction();
    $result = pg_prepare($db_conn, 'object_id', $queryForCheck);
    $resultQuery = pg_execute($db_conn, 'object_id', array((int) $object_id));

    $numRows = pg_num_rows($resultQuery);
    i_closeDatabaseConnection($db_conn);
    if ($numRows > 0) {
        $resultCheck = true;
    }

    return $resultCheck;
}

function i_findAvgValueSignalForObject($parameters)
{
    $avgValue = 0.00;
    $arrAvgValues = array();

    $listArchiveTables = i_getListArchiveTables($parameters, $parameters['time_period']);

    $db_conn = i_connectionToDBArchive();
    foreach ($listArchiveTables as $nameArchiveTable) {
        $sqlQuery = 'select avg(signal_archive_float_value) as avg_value from '
            . $nameArchiveTable
            . ' where signal_id in '
            . $parameters['string_signal_id'];
        $resultQuery = pg_query($db_conn, $sqlQuery);
        $numRows = pg_num_rows($resultQuery);
        if ($numRows > 0) {
            $arrAvgValues[] = (float) pg_fetch_all_columns($resultQuery)[0];
        }
    }
    i_closeDatabaseConnection($db_conn);

    $sumValues = 0.00;
    if (count($arrAvgValues) > 0) {
        foreach ($arrAvgValues as $avgValueFromSpecificTable) {
            $sumValues += $avgValueFromSpecificTable;
        }
        $avgValue = $sumValues/count($arrAvgValues);
    }

    return $avgValue;
}

function i_findCalculationRuleSpecific($parameter): array
{
    $arrayCalculationRules = array();

    $queryCalculationRule = 'select * from calculation_rule where calculation_rule_del=0 and calculation_rule_id=$1';

    $db_conn = i_connectionToCalculationRule();
    $result = pg_prepare($db_conn, 'calc_rule_id', $queryCalculationRule);
    $resultQuery = pg_execute($db_conn, 'calc_rule_id', array($parameter['manual_rule_id']));
    $numRows = pg_num_rows($resultQuery);
    if ($numRows > 0) {
        $arrayCalculationRules = pg_fetch_all($resultQuery);
    }
    i_closeDatabaseConnection($db_conn);

    return $arrayCalculationRules;
}

function i_findCalculationRuleCurrentTime($dataForSearch): array
{
    $arrayCalculationRules = array();
    $db_conn = i_connectionToCalculationRule();

    $queryCalculationRule = 'select * from calculation_rule where calculation_rule_del = 0 and next_calculated_date <= $1';

    $result = pg_prepare($db_conn, 'current_time', $queryCalculationRule);
    $resultQuery = pg_execute($db_conn, 'current_time', array($dataForSearch['date'][0]));

    $numRows = pg_num_rows($resultQuery);
    if ($numRows > 0) {
        $arrayCalculationRules = pg_fetch_all($resultQuery);
    }
    i_closeDatabaseConnection($db_conn);

    return $arrayCalculationRules;
}

function i_findCalculationRuleWithStatusInProcess(): array
{
    $arrayCalculationRules = array();

    $query = 'select * from calculation_rule where calculation_rule_del = 0 and calculation_rule_status = \'in_process_calculation...\' order by calculation_rule_status_ut';
    $db_conn = i_connectionToCalculationRule();
    $result = pg_query($db_conn, $query);
    $numRows = pg_num_rows($result);
    if ($numRows > 0) {
        $arrayCalculationRules = pg_fetch_all($result);
    }
    i_closeDatabaseConnection($db_conn);
    return $arrayCalculationRules;
}

function i_findLastValueSignalForObject($parameters): float
{
    $lastValue = 0.00;

    //в dev-режиме тренируемся с таблицей signal_archive_float_2021_6_9
    if (WORK_MODE == 'dev') {
        $archive_table_name = "signal_archive_float_2021_6_9";
    } else {
        //какую таблицу используем для расчета/записи значения
        $archive_table_name = "signal_archive_float_{$parameters['date']['year']}_{$parameters['date']['mon']}_{$parameters['date']['mday']}";
    }

    $sqlQuery = 'select signal_archive_float_value as last_value from '
                    . $archive_table_name
                    .' where signal_id in '
                    . $parameters['string_signal_id']
                    .' order by signal_archive_float_dt desc, signal_archive_float_value desc limit 1';

    $db_conn = i_connectionToDBArchive();
    $resultQuery = pg_query($db_conn, $sqlQuery);

    $numRows = pg_num_rows($resultQuery);
    if ($numRows > 0) {
        $lastValue = (float) pg_fetch_all_columns($resultQuery)[0];
    }

    i_closeDatabaseConnection($db_conn);

    return $lastValue;
}

function i_findMaxValueSignalForObject($parameters)
{
    $maxValue = 0.00;
    $arrMaxValues = array();

    $listArchiveTables = i_getListArchiveTables($parameters, $parameters['time_period']);

    $db_conn = i_connectionToDBArchive();
    foreach ($listArchiveTables as $nameArchiveTable) {
        $sqlQuery = 'select max(signal_archive_float_value) as max_value from '
            . $nameArchiveTable
            . ' where signal_id in '
            . $parameters['string_signal_id'];
        $resultQuery = pg_query($db_conn, $sqlQuery);
        $numRows = pg_num_rows($resultQuery);
        if ($numRows > 0) {
            $arrMaxValues[] = (float) pg_fetch_all_columns($resultQuery)[0];
        }
    }
    i_closeDatabaseConnection($db_conn);

    if (count($arrMaxValues)) {
        rsort($arrMaxValues);
        $maxValue = reset($arrMaxValues);
    }

    return $maxValue;
}

function i_findMinValueSignalForObject($parameters)
{
    $minValue = 0.00;
    $arrMinValues = array();

    $listArchiveTables = i_getListArchiveTables($parameters, $parameters['time_period']);

    $db_conn = i_connectionToDBArchive();
    foreach ($listArchiveTables as $nameArchiveTable) {
        $sqlQuery = 'select min(signal_archive_float_value) as min_value from '
            . $nameArchiveTable
            . ' where signal_id in '
            . $parameters['string_signal_id'];
        $resultQuery = pg_query($db_conn, $sqlQuery);
        $numRows = pg_num_rows($resultQuery);
        if ($numRows > 0) {
            $arrMinValues[] = (float) pg_fetch_all_columns($resultQuery)[0];
        }
    }
    i_closeDatabaseConnection($db_conn);

    if (count($arrMinValues)) {
        $arrMinValues = array_unique($arrMinValues);
        asort($arrMinValues);
        $minValue = reset($arrMinValues);
    }

    return $minValue;
}

function i_findQuantityDeviceFailForApartment($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id
                            from apartment a,
                                device d,
                                signal s,
                                signal_dict sd,
                                signal_tags_compl stc,
                                signal_tags st,
                                device_tags_compl dtc,
                                device_tags dt
                            where a.apartment_del = 0 and a.apartment_id = $1
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and d.device_online_state = $2
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'quantity_device_fail_apartment', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'quantity_device_fail_apartment', array($parameters['object_id'], 0));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceFailForBMS($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id
                            from bms b2,
                                complex c,
                                building b,
                                apartment a,
                                device d,
                                signal s,
                                signal_dict sd,
                                signal_tags_compl stc,
                                signal_tags st,
                                device_tags_compl dtc,
                                device_tags dt
                            where b2.bms_id = $1
                            and c.bms_id = b2.bms_id
                            and b.complex_id = c.complex_id
                            and a.building_id = b.building_id
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and d.device_online_state = $2
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'quantity_device_fail_bms', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'quantity_device_fail_bms', array($parameters['object_id'], 0));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceFailForBuilding($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id
                            from building b,
                                apartment a,
                                device d,
                                signal s,
                                signal_dict sd,
                                signal_tags_compl stc,
                                signal_tags st,
                                device_tags_compl dtc,
                                device_tags dt
                            where b.building_id = $1
                            and a.building_id = b.building_id
                            and d.apartment_id = a.apartment_id
                            and d.device_online_state = $2
                            and s.device_id = d.device_id                            
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'quantity_device_fail_building', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'quantity_device_fail_building', array($parameters['object_id'], 0));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceFailForComplex($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id
                            from complex c,
                                building b,
                                apartment a,
                                device d,
                                signal s,
                                signal_dict sd,
                                signal_tags_compl stc,
                                signal_tags st,
                                device_tags_compl dtc,
                                device_tags dt
                            where c.complex_id = $1
                            and b.complex_id = c.complex_id
                            and a.building_id = b.building_id
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and d.device_online_state = $2
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                                . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                                . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'quantity_device_fail_complex', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'quantity_device_fail_complex', array($parameters['object_id'], 0));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceForApartment($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id 
                            from apartment a ,
                                device d ,
                                signal s ,
                                signal_dict sd ,
                                signal_tags_compl stc ,
                                signal_tags st ,
                                device_tags_compl dtc ,
                                device_tags dt 
                            where a.apartment_del = 0 and a.apartment_id = $1
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'quantity_device_apartment', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'quantity_device_apartment', array($parameters['object_id']));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceForBuilding($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id 
                            from building b,
                                apartment a ,
                                device d ,
                                signal s ,
                                signal_dict sd ,
                                signal_tags_compl stc ,
                                signal_tags st ,
                                device_tags_compl dtc ,
                                device_tags dt 
                            where b.building_id = $1
                            and a.building_id = b.building_id  
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'quantity_device_building', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'quantity_device_building', array($parameters['object_id']));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceForBMS($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id 
                            from bms bms,
                                complex c,
                                building b,
                                apartment a ,
                                device d ,
                                signal s ,
                                signal_dict sd ,
                                signal_tags_compl stc ,
                                signal_tags st ,
                                device_tags_compl dtc ,
                                device_tags dt 
                            where bms.bms_id = $1
                            and c.bms_id = $1
                            and b.complex_id = c.complex_id
                            and a.building_id = b.building_id  
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'quantity_device_bms', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'quantity_device_bms', array($parameters['object_id']));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceForComplex($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id 
                            from complex c,
                                building b,
                                apartment a ,
                                device d ,
                                signal s ,
                                signal_dict sd ,
                                signal_tags_compl stc ,
                                signal_tags st ,
                                device_tags_compl dtc ,
                                device_tags dt 
                            where c.complex_id = $1
                            and b.complex_id = c.complex_id
                            and a.building_id = b.building_id  
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];
    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'quantity_device_complex', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'quantity_device_complex', array($parameters['object_id']));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceWithNoDataAvailableForApartment($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id
                            from apartment a,
                                device d,
                                signal s,
                                signal_dict sd,
                                signal_tags_compl stc,
                                signal_tags st,
                                device_tags_compl dtc,
                                device_tags dt
                            where a.apartment_del = 0 and a.apartment_id = $1
                            and d.device_last_activity < $2
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'no_data_available_apartment', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'no_data_available_apartment', array(
        $parameters['object_id'],
        $parameters['startTimeForCalculationRule']
    ));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceWithNoDataAvailableForBMS($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id
                            from bms b2, 
                                 complex c,
                                 building b,
                                apartment a,
                                device d,
                                signal s,
                                signal_dict sd,
                                signal_tags_compl stc,
                                signal_tags st,
                                device_tags_compl dtc,
                                device_tags dt
                            where b2.bms_id = $1
                            and c.bms_id = b2.bms_id
                            and b.complex_id = c.complex_id
                            and a.building_id = b.building_id
                            and d.device_last_activity < $2
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'no_data_available_bms', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'no_data_available_bms', array(
        $parameters['object_id'],
        $parameters['startTimeForCalculationRule']
    ));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceWithNoDataAvailableForBuilding($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id
                            from building b,
                                apartment a,
                                device d,
                                signal s,
                                signal_dict sd,
                                signal_tags_compl stc,
                                signal_tags st,
                                device_tags_compl dtc,
                                device_tags dt
                            where b.building_id = $1
                            and a.building_id = b.building_id
                            and d.device_last_activity < $2
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'no_data_available_building', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'no_data_available_building', array(
        $parameters['object_id'],
        $parameters['startTimeForCalculationRule']
    ));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findQuantityDeviceWithNoDataAvailableForComplex($parameters, $device_tags, $signal_tags): int
{
    $resultQuery = 0;
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryQuantityDevice = 'select distinct d.device_id
                            from complex c,
                                 building b,
                                apartment a,
                                device d,
                                signal s,
                                signal_dict sd,
                                signal_tags_compl stc,
                                signal_tags st,
                                device_tags_compl dtc,
                                device_tags dt
                            where c.complex_id = $1
                            and b.complex_id = c.complex_id
                            and a.building_id = b.building_id
                            and d.device_last_activity < $2
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $query = pg_prepare($db_conn, 'no_data_available_complex', $queryQuantityDevice);
    $query = pg_execute($db_conn, 'no_data_available_complex', array(
        $parameters['object_id'],
        $parameters['startTimeForCalculationRule']
    ));
    $numRows = pg_num_rows($query);
    i_closeDatabaseConnection($db_conn);

    if ($numRows > 0) {
        $resultQuery = $numRows;
    }

    return $resultQuery;
}

function i_findSignalsForApartment(int $id, array $device_tags, array $signal_tags): array
{
    $dataSignalId = array();
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryListSignals = 'select distinct s.signal_id
                            from apartment a, 
                                    device d,
                                    signal s,
                                    signal_dict sd,
                                    signal_tags_compl stc,
                                    signal_tags st,
                                    device_tags_compl dtc,
                                    device_tags dt
                            where a.apartment_id = $1
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id 
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $resultQuery = pg_prepare($db_conn, 'list_signal_id_apartment', $queryListSignals);
    $resultQuery = pg_execute($db_conn, 'list_signal_id_apartment', array($id));
    $numRows = pg_num_rows($resultQuery);
    if ($numRows > 0) {
        $dataSignalId = pg_fetch_all($resultQuery);
    }
    i_closeDatabaseConnection($db_conn);

    return $dataSignalId;
}

function i_findSignalsForBMS($id, $device_tags, $signal_tags): array
{
    $dataSignalId = array();
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryListSignals = 'select distinct s.signal_id
                            from bms b2,
                                    complex c,
                                    building b,
                                    apartment a, 
                                    device d,
                                    signal s,
                                    signal_dict sd,
                                    signal_tags_compl stc,
                                    signal_tags st,
                                    device_tags_compl dtc,
                                    device_tags dt
                            where b2.bms_id = $1
                            and c.bms_id = b2.bms_id
                            and b.complex_id = c.complex_id
                            and a.building_id = b.building_id
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id 
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $resultQuery = pg_prepare($db_conn, 'list_signal_id_bms', $queryListSignals);
    $resultQuery = pg_execute($db_conn, 'list_signal_id_bms', array($id));
    $numRows = pg_num_rows($resultQuery);
    if ($numRows > 0) {
        $dataSignalId = pg_fetch_all($resultQuery);
    }
    i_closeDatabaseConnection($db_conn);

    return $dataSignalId;
}

function i_findSignalsForBuilding($id, $device_tags, $signal_tags): array
{
    $dataSignalId = array();
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryListSignals = 'select distinct s.signal_id
                            from building b,
                                    apartment a, 
                                    device d,
                                    signal s,
                                    signal_dict sd,
                                    signal_tags_compl stc,
                                    signal_tags st,
                                    device_tags_compl dtc,
                                    device_tags dt
                            where b.building_id = $1
                            and a.building_id = b.building_id
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id 
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $resultQuery = pg_prepare($db_conn, 'list_signal_id_building', $queryListSignals);
    $resultQuery = pg_execute($db_conn, 'list_signal_id_building', array($id));
    $numRows = pg_num_rows($resultQuery);
    if ($numRows > 0) {
        $dataSignalId = pg_fetch_all($resultQuery);
    }
    i_closeDatabaseConnection($db_conn);

    return $dataSignalId;
}

function i_findSignalsForComplex($id, $device_tags, $signal_tags): array
{
    $dataSignalId = array();
    $tags = i_getStringTagsNameForQuery($device_tags, $signal_tags);

    $queryListSignals = 'select distinct s.signal_id
                            from complex c,
                                    building b,
                                    apartment a, 
                                    device d,
                                    signal s,
                                    signal_dict sd,
                                    signal_tags_compl stc,
                                    signal_tags st,
                                    device_tags_compl dtc,
                                    device_tags dt
                            where c.complex_id = $1
                            and b.complex_id = c.complex_id
                            and a.building_id = b.building_id
                            and d.apartment_id = a.apartment_id
                            and s.device_id = d.device_id
                            and sd.signal_dict_id = s.signal_dict_id
                            and stc.signal_dict_id = s.signal_dict_id
                            and st.signal_tags_id = stc.signal_tags_id 
                            and dtc.device_model_id = d.device_model_id
                            and dt.device_tags_id = dtc.device_tags_id'
                            . $tags['andDtTagName'] . $tags['stringDeviceTagsName']
                            . $tags['andStTagName'] . $tags['stringSignalTagsName'];

    $db_conn = i_connectionToDBProduction();
    $resultQuery = pg_prepare($db_conn, 'list_signal_id_complex', $queryListSignals);
    $resultQuery = pg_execute($db_conn, 'list_signal_id_complex', array($id));
    $numRows = pg_num_rows($resultQuery);
    if ($numRows > 0) {
        $dataSignalId = pg_fetch_all($resultQuery);
    }
    i_closeDatabaseConnection($db_conn);

    return $dataSignalId;
}

function i_saveResultInArchive($resultExecCalcRule, $parameterSpecificRule, $date)
{
    $resultQuery = false;

    //для записи нам нужны следующие данные
    // имя таблицы архива - signal_archive_float_yyyy_mm_dd
    // значение рассчитанное по calc_rule - signal_archive_float_value
    // время записи в архив - signal_archive_float_dt
    // отрицательный номер правила - signal_id

    $signal_archive_float_dt = time();
    $signal_id = -1 * ($parameterSpecificRule['calculation_rule_id']);

    if (WORK_MODE == 'dev') {
        //в режиме разработки
        $tmpl_table_name = "signal_archive_float_2020_1_1";
    } else {
        $tmpl_table_name = "signal_archive_float_{$date['year']}_{$date['mon']}_{$date['mday']}";
    }

    $db_conn = i_connectionToDBArchive();
    $sqlQuery = 'insert into ' . $tmpl_table_name . ' (signal_archive_float_value, signal_archive_float_dt, signal_id) values ($1, $2, $3)';
    $resultQuery = pg_prepare($db_conn, 'save_value_calc_rule', $sqlQuery);
    $resultQuery = pg_execute($db_conn, 'save_value_calc_rule', array(
        (float) $resultExecCalcRule,
        $signal_archive_float_dt,
        (int) $signal_id
    ));

    i_closeDatabaseConnection($db_conn);

    return $resultQuery;
}

function i_updateCalcRuleLastNextDate($newParameters)
{
    $db_conn = i_connectionToCalculationRule();
    $sqlQuery = 'update calculation_rule set last_calculated_date = $1, next_calculated_date = $2, calculation_rule_status = \'waiting_next_calculation...\' where calculation_rule_id = $3';
    $result = pg_prepare($db_conn, 'upd_last_next_date', $sqlQuery);
    $resultUpdate = pg_execute($db_conn, 'upd_last_next_date', array(
        $newParameters['last_calculated_date'],
        $newParameters['next_calculated_date'],
        $newParameters['calculation_rule_id']
    ));
    i_closeDatabaseConnection($db_conn);

    return $resultUpdate;
}

function i_updateFieldStatusForSpecificCalculationRule($calculation_rule_id): bool
{
    $db_conn = i_connectionToCalculationRule();
    $sqlQuery = 'update calculation_rule set calculation_rule_status = \'in_process_calculation...\' where calculation_rule_id = $1';
    $result = pg_prepare($db_conn, 'upd_calc_status', $sqlQuery);
    $resultQuery = pg_execute($db_conn, 'upd_calc_status', array($calculation_rule_id));

    i_closeDatabaseConnection($db_conn);

    return (boolean)$resultQuery;
}


//локальные функции, вспомогательные
function i_getListArchiveTables($parameters, $time_period): array
{
    $quantityArchiveTables = i_getQuanDaysFromTimePeriod($parameters['date'][0], $time_period);

    if (WORK_MODE == 'dev') {
        $breakPoint = $parameters['date'][0] - (86400 * 30);
    } else {
        $breakPoint = $parameters['date'][0];
    }

    $listArchiveTables = array();
    for ($i = 0; $i < $quantityArchiveTables; ++$i) {
        $prevTimeStamp = $breakPoint - (int) (86400 * $i);
        $prevDate = getdate($prevTimeStamp);
        $listArchiveTables[] = 'signal_archive_float_' . (int) $prevDate['year'] . '_' . (int) $prevDate['mon'] . '_' . (int) $prevDate['mday'];
    }

    return $listArchiveTables;
}

function i_getQuanDaysFromTimePeriod($timestamp, $time_period): int
{
    $quantityDay = 0;
    $deltaDay = 0;

    preg_match("#^(\d?\d)([d|w|m])$#", $time_period, $resultTimePeriod);

    switch ($resultTimePeriod[2]) {
        case 'd':
            $quantityDay = $resultTimePeriod[1];
            break;
        case 'w':
            $quantityDay = $resultTimePeriod[1] * 7;
            break;
        case 'm':
            $arrDate = getdate($timestamp);
            $quantityDay = $arrDate['yday'];
            $newTimeStamp = mktime(0, 0, 0, ($arrDate['mon'] - (int)$resultTimePeriod[1]), 24, 2021);
            $startCalcPeriod = getdate($newTimeStamp);
            if ((int) $startCalcPeriod['year'] < (int) $arrDate['year']) {
                if (date('L', mktime(0, 0, 0, ($arrDate['mon'] - (int)$resultTimePeriod[1]), 24, 2021))) {
                    $deltaDay = 366 - $startCalcPeriod['yday'];
                } else {
                    $deltaDay = 365 - $startCalcPeriod['yday'];
                }
            } else {
                $quantityDay -= $startCalcPeriod['yday'];
            }
            $quantityDay += $deltaDay;
            break;
    }

    return (int) $quantityDay;
}

function i_getStringTagsName(array $tags_name): string
{
    if (count($tags_name) > 0) {
        $stringTagName = '(';
        if (count($tags_name) > 1) {
            foreach ($tags_name as $tag_name) {
                $stringTagName .= "'{$tag_name}'" . ', ';
            }
        } elseif(count($tags_name) == 1) {
            $stringTagName .= "'" . array_shift($tags_name) . "'";
        }

        $stringTagName = rtrim($stringTagName, ' ,');
        $stringTagName .= ')';
    } else {
        $stringTagName = '';
    }

    return $stringTagName;
}

function i_getStringTagsNameForQuery($device_tags, $signal_tags): array
{
    $tags = [];

    $tags['stringDeviceTagsName'] = i_getStringTagsName($device_tags);
    $tags['stringSignalTagsName'] = i_getStringTagsName($signal_tags);

    if(!empty($tags['stringDeviceTagsName'])) {
        $tags['andDtTagName'] = ' and dt.tag_name in ';
    } else {
        $tags['andDtTagName'] = '';
    }

    if (!empty($tags['stringSignalTagsName'])) {
        $tags['andStTagName'] = ' and st.tag_name in ';
    } else {
        $tags['andStTagName'] = '';
    }

    return $tags;
}
