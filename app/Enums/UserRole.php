<?php
namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case COORDINATOR = 'coordinator';
    case EXECUTOR = 'executor';
    case TRANSFER_EXECUTOR = 'transfer_executor';
}
