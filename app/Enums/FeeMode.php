<?php
namespace App\Enums;

enum FeeMode: string
{
    case INCLUDED = 'included';
    case EXCLUDED = 'excluded';

    public function label(): string
    {
        return __("enums.fee_mode.$this->value");
    }
}
