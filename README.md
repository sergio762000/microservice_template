# Archive

## Развертывание приложения
1. Клонируйте репозиторий.
```
git@gitlab.com:unicorn-backend/archive.git
``` 
	или
```
https://gitlab.com/unicorn-backend/archive.git
```

2. Добавление полей в таблицу universal_types
    - в файле main.sql - содержится функция insert_if_not_exists_universal_types(), которая добавляет новые значения в таблицу
      universal_types. Новые значения имеют тип - signal_report
3. Создание таблицы calculation_rule
    - в файле calc_rule.sql находится код для создания таблицы calculation_rule, триггера и триггерной функции.
4. Приложение может функционировать в двух (пока) режимах - в "__dev__" или "__prod__".
   Переключение между режимами производится изменением настройки в файле __config/application.conf__.
   При работе приложения в режиме "__dev__" включен лог записи входящих запросов ({home_dir}/log/phpInput.log).
5. Приложение записывает (в зависимости от режима работы) служебную информацию в файлы: 
   - PDO.log (во всех режимах) - содержимое Exception при работе PDO;
   - phpInput.log (в dev режиме) - содержимое запросов HTTP-методов. 

Сервис __archive__ содержит 2 функциональных блока:

* __calculation_rule (CR)__- создание, просмотр, удаление правил расчета (web-приложение);

* __crawler_calc_rule (CCR)__ - обходчик-исполнитель правил расчета (консольное приложение). Другое название console_calculation_rule.
Описание CR находится в файле README_CR.md, CCR - README_CCR.md

## Работа с блоком CR - см файл README_CR.md

## Работа с блоком CCR - см файл README_CCR.md


## Структура каталогов
Корневой каталог - **archive**

Файл запуска приложения - **index.php**
* __app__
    * корневой каталог приложения, содержит контроллеры, бизнес-логику, логику работы с хранилищами.

* __config__
    * конфигурационные файлы для работы приложения: подключение к БД, список обрабатываемых полей.

* __coreapp__
    * базовое ядро приложения. Несет на себе функции фронт-контроллера, автозагрузчика классов приложения,
      подключения к БД, фиксирования сбойных ситуаций при работе с БД.

* __log__
    * директория для записи log-файлов. Разрешить пользователю www-data писать в эту папку.

* __temp__
    * директория для хранения временной информации (необязательна).

* __vendor__
    * директория для сторонних пакетов (необязательна).

## Конфигурация (каталог {home_dir}/config)
* __application.conf__
    * содержит информацию о режиме работы приложения;
* __calculation_list_fields.conf__
    * содержит список полей таблицы calculation_rule. При изменении структуры таблицы необходимо вносить своевременные
      правки в указанный файл
* __database.example.conf__ 
    * содержит шаблон для указания параметров подключения к БД. 
  Для правильной настройки подключений к базам данных следует сделать следующее:
      1. Скопируйте файл ___database.example.conf___ в файл __database.calculation_rule.conf__ и 
            укажите необходимые параметры актуального для вашей работы подключения к БД, 
            где будет находиться таблица ___calculation_rule___; 
      2. В случае, если таблица ___calculation_rule___ будет находиться в отдельной БД, тогда 
            скопируйте файл ___database.example.conf___ в файл __database.production.conf__ и 
            укажите необходимые параметры актуального для вашей работы подключения к БД,
            где будут находиться таблицы bms, complex, building, apartment, device, signal, device_tags, signal_tags и т.п.;
            Иначе, настройки будут взяты из файла __database.calculation_rule.conf__;
      3. Если база данных с архивами не совпадает по настройкам подключения с __database.calculation_rule.conf__, 
            скопируйте файл ___database.example.conf___ в файл __database.archive.conf__. Произведите настройку параметров.
* __PrivilegedTypeSignal.php.php__
    * список привилегированных типов сигналов. 
      Если указан в запросе такое тип сигнала, то пустой параметр tags_signal - недопустим;
* __Routes.php__
    * список маршрутов приложения (правило обработки входящих запросов);
    
###Информация для разработчиков

Учитывайте, что БД может содержать триггеры, триггерные функции и другие инструменты БД, которые делают незаметную, но важную работу. 
Например, при любом изменении правила расчета изменяется поле ___calculation_rule_status_ut___.