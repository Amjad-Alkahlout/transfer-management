<?php

namespace App\Enums;

enum CapitalTransactionType: string
{
    case CUSTOMER_TRANSFER = 'customer_transfer';

    case INTERNAL_TRANSFER = 'internal_transfer';

    case MANUAL_ADJUSTMENT = 'manual_adjustment';
    case OPENING_BALANCE = 'opening_balance';

    case TRANSFER_EXPENSE='transfer_expense';

    case PROFIT_WITHDRAWAL = 'profit_withdrawal';

    case TRANSFER_CANCELLATION = 'transfer_cancellation';
}
