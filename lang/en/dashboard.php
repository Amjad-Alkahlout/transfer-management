<?php

return [

    'page' => [
        'title' => 'Dashboard',
        'description' => 'Money Transfer System Overview',
    ],

    'financial' => [
        'title' => 'Financial Overview',
        'description' => 'Current balances across company accounts.',

        'gaza_capital' => 'Gaza Capital',
        'uae_capital_usd' => 'UAE Capital (USD)',
        'uae_capital_aed' => 'UAE Capital (AED)',
        'profit' => 'Profit',
        'customer_receivables' => 'Customer Receivables (AED)',

        'current_balance' => 'Current Balance',
        'net_profit' => 'Net Profit',
        'outstanding_amount' => 'Outstanding Amount',
    ],

    'quick_actions' => [
        'title' => 'Quick Actions',

        'create_transfer' => 'Create Transfer',

        'transfers' => 'Transfers',
        'capital_transfers' => 'Capital Transfers',
        'capital_accounts' => 'Capital Accounts',
        'exchange_rates' => 'Exchange Rates',
        'commission_rules' => 'Commission Rules',
    ],

    'transfers' => [
        'title' => 'Transfer Overview',
        'description' => 'Current transfer status summary.',

        'status' => 'Status',
        'total' => 'Total',
    ],

    'payments' => [
        'title' => 'Payment Overview',
        'description' => 'Current payment status summary.',

        'status' => 'Status',
        'total' => 'Total',
    ],

    'exchange_rates' => [
        'title' => 'Exchange Rates',
        'description' => 'Current exchange rates.',

        'currency' => 'Currency',
        'rate_to_usd' => 'Rate to USD',
    ],

    'commission_rules' => [
        'title' => 'Commission Rules',
        'description' => 'Configured commission rules.',

        'currency' => 'Currency',
        'total_rules' => 'Total Rules',
    ],

    'telegram' => [

        'connected' => 'Telegram',

        'link' => 'Link Telegram',

        'modal_title' => 'Telegram Notifications',

        'connected_title' => 'Telegram Connected',

        'connected_description' => 'Your Telegram account is connected and will receive transfer notifications.',

        'status' => 'Status',

        'enabled' => 'Enabled',

        'disconnect' => 'Disconnect',

        'relink' => 'Relink',

        'close' => 'Close',

        'link_title' => 'Link Telegram',

        'link_description' => 'Connect your Telegram account to receive instant notifications whenever a transfer requires your action.',

        'step_1' => 'Step 1',

        'step_2' => 'Step 2',

        'open_bot' => 'Open Bot',

        'open_bot_description' => 'Open our Telegram bot.',

        'send_command' => 'Send the following command:',

        'copy_command' => 'Copy Command',

        'code_expire' => 'This code expires in 10 minutes.',
    ],

    'messages' => [
        'telegram_linked' => 'Telegram linked successfully.',
        'telegram_disconnected' => 'Telegram disconnected successfully.',
    ],

];
