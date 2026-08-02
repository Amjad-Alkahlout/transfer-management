<?php

namespace App\Enums;

enum DeviceType: string
{
    case MOBILE = 'mobile';
    case DESKTOP = 'desktop';

    public function label(): string
    {
        return __("enums.device_type.$this->value");
    }
}
