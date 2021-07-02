CREATE OR REPLACE FUNCTION create_if_not_exists (table_name text)
    RETURNS text AS
$_$
BEGIN

    IF EXISTS (
            SELECT *
            FROM   pg_catalog.pg_tables
            WHERE    tablename  = table_name
        ) THEN
        RETURN 'TABLE ' || '''' || table_name || '''' || ' ALREADY EXISTS';
    ELSE
        EXECUTE 'CREATE TABLE '||table_name||' ();';
        RETURN 'CREATED';
    END IF;

END;
$_$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION add_column(schema_name TEXT, table_name TEXT,
                                      column_name TEXT, data_type TEXT, dop TEXT, bigserial1 TEXT)
    RETURNS BOOLEAN
AS
$BODY$
DECLARE
    _tmp text;
BEGIN

    EXECUTE format('SELECT COLUMN_NAME FROM information_schema.columns WHERE
    table_schema=%L
    AND table_name=%L
    AND column_name=%L', schema_name, table_name, column_name)
        INTO _tmp;

    IF _tmp IS NOT NULL THEN
        RAISE NOTICE 'Column % already exists in %.%', column_name, schema_name, table_name;
        RETURN FALSE;
    END IF;

    EXECUTE format('ALTER TABLE %I.%I ADD COLUMN %I %s;', schema_name, table_name, column_name, data_type);

    RAISE NOTICE 'Column % added to %.%', column_name, schema_name, table_name;

    IF bigserial1<>'' THEN
        EXECUTE format('
   CREATE SEQUENCE %I_seq
   INCREMENT 1
   MINVALUE 1
   MAXVALUE 9223372036854775807
   START 1
   CACHE 1;
   ALTER TABLE %I_seq
   OWNER TO postgres;',column_name,column_name);
    END IF;
    IF bigserial1<>'' THEN
        EXECUTE format('ALTER TABLE %I ALTER COLUMN %I SET NOT NULL;',table_name, column_name);
        EXECUTE format('ALTER TABLE %I ALTER COLUMN %I SET DEFAULT nextval(''%I_seq''::regclass);',table_name, column_name, column_name);
    END IF;

    IF dop<>'' THEN
        EXECUTE format('ALTER TABLE %I.%I ALTER COLUMN %I SET %s;', schema_name, table_name, column_name, dop);
    END IF;

    RETURN TRUE;
END;
$BODY$
    LANGUAGE 'plpgsql';

select create_if_not_exists('calculation_rule');

select add_column('public', 'calculation_rule', 'calculation_rule_id', 'bigint', '', '1');
select add_column('public', 'calculation_rule', 'calculation_rule_name', 'character varying', '', '');
select add_column('public', 'calculation_rule', 'object_type', 'character varying', 'DEFAULT ''''::character varying', '');
select add_column('public', 'calculation_rule', 'object_id', 'bigint', '', '');
select add_column('public', 'calculation_rule', 'type_signal', 'character varying', 'DEFAULT ''''::character varying', '');
select add_column('public', 'calculation_rule', 'tags_device', 'character varying', '', '');
select add_column('public', 'calculation_rule', 'tags_signal', 'character varying', '', '');
select add_column('public', 'calculation_rule', 'time_period', 'character varying', '', '');
select add_column('public', 'calculation_rule', 'calculation_schedule', 'integer ARRAY[5]', 'DEFAULT ''{0, 0, -1, -1, -1}''::integer[]', '');
select add_column('public', 'calculation_rule', 'calculation_rule_status', 'character varying', 'DEFAULT ''новое''::character varying', '');
select add_column('public', 'calculation_rule', 'calculation_rule_status_ut', 'bigint', 'DEFAULT extract(epoch from LOCALTIMESTAMP(0))::bigint::integer', '');
select add_column('public', 'calculation_rule', 'last_calculated_date', 'bigint', 'DEFAULT ''0''::integer', '');
select add_column('public', 'calculation_rule', 'next_calculated_date', 'bigint', 'DEFAULT ''0''::integer', '');
select add_column('public', 'calculation_rule', 'calculation_rule_del', 'bigint', 'DEFAULT ''0''::integer', '');


create or replace function trigger_change_calculation_rule_status_ut()
returns trigger as
$body$
begin
    if (TG_OP = 'INSERT' OR TG_OP = 'UPDATE') then
        new.calculation_rule_status_ut = extract(epoch from LOCALTIMESTAMP(0))::bigint::integer;
    return new;
    elseif (TG_OP = 'DELETE') then
            old.calculation_rule_status_ut = extract(epoch from LOCALTIMESTAMP(0))::bigint::integer;
    return old;
    end if;
end;
$body$ language plpgsql;

create trigger t_trigger_change_calculation_rule_status_ut
    before insert or update or delete on calculation_rule
    for each row
    execute procedure trigger_change_calculation_rule_status_ut();
