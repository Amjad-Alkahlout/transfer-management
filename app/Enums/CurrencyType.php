<?php
namespace App\Enums;

enum CurrencyType: string
{
    case AED = 'aed';
    case USD = 'usd';
    case ILS = 'ils';

    public function symbol(): string
    {
        return match ($this) {
            self::AED => 'AED',
            self::USD => 'USD',
            self::ILS => 'ILS',
        };
    }
}

