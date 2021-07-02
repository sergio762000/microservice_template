<?php


namespace archive\config;


trait PrivilegedTypeSignal
{
    private function getPrivilegedTypeSignal(): array
    {
        return array('last_value', 'avg_value', 'max_value', 'min_value', 'delta_val_lst_period');
    }
}
