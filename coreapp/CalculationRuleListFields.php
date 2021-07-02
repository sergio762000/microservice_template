<?php


namespace archive\coreapp;


trait CalculationRuleListFields
{
    public function getCRListFields()
    {
        $listFields = array();
        $parseIniFile = parse_ini_file(__DIR__ . '/../config/calculation_list_fields.conf');
        foreach ($parseIniFile as $key => $value) {
            $listFields[$key] = explode(',', str_replace(' ', '', $value));
        }

        return $listFields;
    }
}
