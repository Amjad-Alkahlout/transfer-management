<?php

return [

    'capital_transfer' => [

        'same_account' => 'Source and destination accounts cannot be the same.',

        'source_must_be_capital' => 'Source account must be a capital account.',

        'destination_must_be_capital' => 'Destination account must be a capital account.',

        'source_inactive' => 'Source account is inactive.',

        'destination_inactive' => 'Destination account is inactive.',

        'insufficient_source_balance' => 'Insufficient balance in the source account.',

        'amount_must_be_positive' => 'Transfer amount must be greater than zero.',

        'cost_cannot_be_negative' => 'Transfer cost cannot be negative.',

        'profit_account_not_found' => 'No profit account found for currency :currency.',

        'invalid_destination_amount' => 'Calculated destination amount is invalid.',

        'insufficient_profit_balance' => 'Insufficient balance in the Gaza profit account.',
        'profit_distribution_no_cost' => 'Profit distribution transfers cannot include a transfer cost.',

    ],
    'commission' => [

        'rule_not_found' => 'No commission rule found for this amount.',

    ],
    'currency_converter' => [

        'exchange_rate_not_found' => 'Exchange rate not found.',

    ],
    'profit_withdrawal' => [

        'invalid_account' => 'Selected account is not a profit account.',

        'inactive_account' => 'Profit account is inactive.',

        'amount_must_be_positive' => 'Amount must be greater than zero.',

        'insufficient_balance' => 'Insufficient balance.',

    ],
    'receive_payment' => [

        'already_paid' => 'This transfer has already been fully paid.',

        'transfer_cancelled' => 'This transfer has been cancelled. No further payments can be received.',

        'payment_exceeds_remaining' => 'Payment amount cannot exceed the remaining balance.',

    ],
    'transfer_calculator' => [

        'included_fee_exceeds_amount' => 'The commission fee for this amount exceeds the amount itself. Please enter a higher amount or switch Fee Mode to EXCLUDED.',

        'fee_exceeds_amount' => 'The commission fee for this amount exceeds the amount itself.',

    ],
    'transfer_cancellation' => [

        'cannot_cancel' => 'This transfer cannot be cancelled.',

    ],
    'transfer_execution' => [

        'insufficient_gaza_capital' => 'Insufficient balance in Gaza capital account.',

    ],
    'capital_account_adjustment' => [
        'inactive_account' => 'Cannot adjust the balance of an inactive account.',
        'amount_must_be_positive' => 'The amount must be greater than zero.',
        'insufficient_balance' => 'The current balance is insufficient to complete the deduction.',
    ],

];
