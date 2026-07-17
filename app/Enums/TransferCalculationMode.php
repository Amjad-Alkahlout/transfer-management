<?php

namespace App\Enums;

enum TransferCalculationMode: string
{
    case RECEIVER_AMOUNT = 'receiver_amount';

    case CUSTOMER_PAYMENT = 'customer_payment';
}
