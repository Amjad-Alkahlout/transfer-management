<?php

namespace App\Enums;

enum Branch: string
{
    case UAE = 'uae';
    case GAZA = 'gaza';

    public function label(): string
    {
        return __("enums.branch.$this->value");
    }
}


