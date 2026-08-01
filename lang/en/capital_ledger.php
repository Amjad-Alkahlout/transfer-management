<?php

return [

    'page' => [
        'title' => 'Capital Ledger',
        'description' => 'View and filter all capital account transactions.',
    ],

    'sections' => [
        'filters' => 'Filters',
        'filters_description' => 'Filter capital transactions using the options below.',
        'transactions' => 'Capital Transactions',
        'transactions_description' => 'Browse all capital account movements.',
    ],

    'filters' => [
        'account' => 'Account',
        'all_accounts' => 'All Accounts',

        'transaction_type' => 'Transaction Type',
        'all_transaction_types' => 'All Transaction Types',

        'from_date' => 'From Date',
        'to_date' => 'To Date',
    ],

    'table' => [
        'account' => 'Account',
        'amount' => 'Amount',
        'direction' => 'Direction',
        'balance_before' => 'Balance Before',
        'balance_after' => 'Balance After',
        'transaction_type' => 'Transaction Type',
        'description' => 'Description',
        'date' => 'Date',
    ],

    'messages' => [
        'empty' => 'No capital transactions found.',
    ],

];
