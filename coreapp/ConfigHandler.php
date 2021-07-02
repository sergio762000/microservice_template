<?php


namespace archive\coreapp;


abstract class ConfigHandler
{

    public static function getApplicationMode()
    {
        $configApplication = parse_ini_file(__DIR__ . '/../config/application.conf');

        return $configApplication['work_mode_app'];
    }
}
