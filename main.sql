-- ADD NEW TYPE IN table universal_type--
CREATE OR REPLACE FUNCTION insert_if_not_exists_universal_types(universal_name_in text, universal_title_in text, universal_type_in text)
    RETURNS text AS
$BODY_FUNCTION$
BEGIN
    IF EXISTS(
            SELECT  *
            FROM    universal_types
            WHERE   universal_name = universal_name_in
        ) THEN
        RETURN 'Param ' || '''' ||universal_name_in|| '''' || ' ALREADY EXISTS';
    ELSE
        EXECUTE 'insert into universal_types (universal_name, universal_title, universal_type) values ('''||universal_name_in||''', '''||universal_title_in||''', '''||universal_type_in||''');';
        RETURN 'insert';
    END IF;
END;
$BODY_FUNCTION$
    LANGUAGE plpgsql;

select insert_if_not_exists_universal_types('last_value', 'Последнее значение сигнала', 'signal_report');
select insert_if_not_exists_universal_types('avg_value', 'Среднее значение сигнала', 'signal_report');
select insert_if_not_exists_universal_types('max_value', 'Максимальное значение сигнала', 'signal_report');
select insert_if_not_exists_universal_types('min_value', 'Минимальное значение сигнала', 'signal_report');
select insert_if_not_exists_universal_types('no_data_available', 'Пустые данные', 'signal_report');
select insert_if_not_exists_universal_types('quantity_device', 'Кол-во устройств', 'signal_report');
select insert_if_not_exists_universal_types('quantity_device_fail', 'Кол-во аварийных устройств', 'signal_report');

-- table universal_types--