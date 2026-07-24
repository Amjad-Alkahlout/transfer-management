<?php

namespace App\Enums;

enum TransactionDirection: string
{
    case IN = 'in';

    case OUT = 'out';

    public function label(): string
    {
        return __("enums.transaction_direction.$this->value");
    }
}
