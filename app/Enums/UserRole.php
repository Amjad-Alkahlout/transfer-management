<?php
namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case COORDINATOR = 'coordinator';
    case EXECUTOR = 'executor';
    case TRANSFER_EXECUTOR = 'transfer_executor';

    public function label(): string
    {
        return __("enums.user_role.$this->value");
    }
}
