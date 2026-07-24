<?php
namespace App\Enums;

enum TransferStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __("enums.transfer_status.$this->value");
    }
}
