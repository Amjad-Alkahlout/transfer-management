<?php
namespace App\Enums;

enum ReceiverMethod: string
{
case BANK = 'bank';

case WALLET = 'wallet';

    public function label(): string
    {
        return __("enums.receiver_method.$this->value");
    }

}
