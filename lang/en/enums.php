<?php

return [

    'branch' => [
        'uae' => 'UAE',
        'gaza' => 'Gaza',
    ],

    'capital_account_type' => [
        'capital' => 'Capital',
        'profit' => 'Profit',
    ],

    'capital_transaction_type' => [
        'customer_transfer' => 'Customer Transfer',
        'internal_transfer' => 'Internal Transfer',
        'manual_adjustment' => 'Manual Adjustment',
        'opening_balance' => 'Opening Balance',
        'transfer_expense' => 'Transfer Expense',
        'profit_withdrawal' => 'Profit Withdrawal',
        'transfer_cancellation' => 'Transfer Cancellation',
        'profit_distribution' => 'Profit Distribution',
    ],

    'currency' => [
        'aed' => 'AED',
        'usd' => 'USD',
        'ils' => 'ILS',
    ],


    'payment_status' => [
        'unpaid' => 'Unpaid',
        'partially_paid' => 'Partially Paid',
        'paid' => 'Paid',
    ],

    'receiver_method' => [
        'bank' => 'Bank Account',
        'wallet' => 'Wallet',
    ],

    'transaction_direction' => [
        'in' => 'Incoming',
        'out' => 'Outgoing',
    ],

    'transfer_calculation_mode' => [
        'receiver_amount' => 'Receiver Amount',
        'customer_payment' => 'Customer Payment',
    ],

    'transfer_status' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'user_role' => [
        'admin' => 'Administrator',
        'coordinator' => 'Coordinator',
        'executor' => 'Executor',
        'transfer_executor' => 'Transfer Executor',
    ],

];
