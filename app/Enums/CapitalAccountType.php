<?php

namespace App\Enums;

enum CapitalAccountType: string
{
    case CAPITAL = 'capital';
    case PROFIT = 'profit';

    public function label(): string
    {
        return __("enums.capital_account_type.$this->value");
    }
}
