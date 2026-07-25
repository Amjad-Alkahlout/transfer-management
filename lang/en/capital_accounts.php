<?php

return [

    'page' => [
        'title' => 'Capital Accounts',
        'description' => 'Manage company capital accounts.',
    ],

    'table' => [
        'title' => 'Capital Accounts',
        'description' => 'All configured capital accounts.',

        'name' => 'Name',
        'branch' => 'Branch',
        'currency' => 'Currency',
        'current_balance' => 'Current Balance',
        'account_type' => 'Account Type',
        'is_active' => 'Status',
        'actions' => 'Actions',
    ],

    'forms' => [

        'add' => [
            'title' => 'Add Capital Account',
            'description' => 'Create a new capital account.',
        ],

        'edit' => [
            'title' => 'Edit Capital Account',
            'description' => 'Update account information.',
        ],

        'withdraw' => [
            'title' => 'Withdraw Profit',
            'description' => 'Withdraw profit from :account',
        ],
        'adjustment' => [
            'title' => 'Manual Balance Adjustment',
            'description' => 'Manually adjust the balance of :account (add or deduct), with a documented reason.',
        ],
        'profit_distribution' => [
            'title' => 'Distribute Profit to UAE',
            'description' => 'Transfer part of the profit account balance (:account) directly to a UAE capital account, with no transfer cost.',
        ],

    ],

    'fields' => [
        'name' => 'Name',
        'branch' => 'Branch',
        'currency' => 'Currency',
        'account_type' => 'Account Type',
        'opening_balance' => 'Opening Balance',
        'amount' => 'Amount',
        'notes' => 'Notes',
        'adjustment_direction' => 'Adjustment Type',
        'adjustment_notes' => 'Adjustment Reason',
        'destination_account' => 'Destination Account (UAE)',
    ],

    'buttons' => [
        'back' => 'Dashboard',
        'add' => 'Add Capital Account',
        'edit' => 'Edit',
        'update' => 'Update Capital Account',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'withdraw_profit' => 'Withdraw Profit',
        'adjust_balance' => 'Adjust Balance',
        'save_adjustment' => 'Save Adjustment',
        'distribute_profit' => 'Distribute to UAE',
    ],

    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'messages' => [
        'created' => 'Capital account added successfully.',
        'updated' => 'Capital account updated successfully.',
        'activated' => 'Capital account activated successfully.',
        'deactivated' => 'Capital account deactivated successfully.',
        'profit_withdrawn' => 'Profit withdrawn successfully.',
        'adjusted' => 'Balance adjusted successfully.',
        'profit_distributed' => 'Profit distributed successfully.',
    ],

    'errors' => [
        'profit_branch_only' => 'Profit accounts can only be created for the Gaza branch.',
        'duplicate_account' => 'An account with the same branch, currency, and account type already exists.',
    ],

    'placeholders' => [
        'select_branch' => 'Select Branch',
        'select_currency' => 'Select Currency',
        'select_account_type' => 'Select Account Type',
        'select_uae_account' => 'Select UAE account',
    ],
    'adjustment_direction' => [
        'add' => 'Add amount',
        'deduct' => 'Deduct amount',
    ],

];
