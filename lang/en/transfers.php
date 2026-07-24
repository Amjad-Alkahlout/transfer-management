<?php

return [

    'page' => [
        'title' => 'Create New Transfer',
        'description' => 'Create a new customer transfer.',
        'update_title' => 'Update Transfer',
        'index_title' => 'Transfers',
        'index_description' => 'Manage customer transfers.',
        'show_title' => 'Transfer Details',
    ],

    'receiver' => [
        'title' => 'Receiver Information',
        'description' => 'Receiver details and delivery method.',
    ],

    'transfer' => [
        'title' => 'Transfer Information',
        'description' => 'Transfer calculation settings.',
    ],

    'payment' => [
        'title' => 'Payment',
        'description' => 'Record an optional payment received during transfer creation.',
    ],

    'additional' => [
        'title' => 'Additional Information',
    ],

    'preview' => [
        'title' => 'Calculation Preview',
        'description' => 'Live calculation based on the current values.',

        'receiver_gets' => 'Receiver Gets',
        'customer_pays' => 'Customer Pays',
        'commission' => 'Commission',
    ],

    'fields' => [
        'calculation_mode' => 'Calculation Mode',
        'receiver_name' => 'Receiver Name',
        'receiver_method' => 'Receiver Method',
        'receiver_amount' => 'Receiver Amount',
        'receiver_account_number' => 'Receiver Bank Account Number',
        'receiver_wallet_number' => 'Receiver Wallet Number',
        'requested_currency' => 'Requested Currency',
        'requested_amount' => 'Requested Amount',
        'fee_mode' => 'Fee Mode',
        'customer_pay_amount' => 'Customer Pay Amount',
        'customer_payable_currency' => 'Customer Payable Currency',
        'customer_pay_currency' => 'Customer Pay Currency',
        'initial_payment' => 'Initial Payment',
        'notes' => 'Notes',
        'transfer_proof' => 'Transfer Proof',
    ],

    'placeholders' => [
        'select_receiver_method' => 'Select Receiver Method',
        'select_requested_currency' => 'Select Requested Currency',
        'select_fee_mode' => 'Select Fee Mode',
        'select_customer_pay_currency' => 'Select Customer Pay Currency',
    ],

    'buttons' => [
        'back' => 'Back',
        'cancel' => 'Cancel',
        'create' => 'Create Transfer',
        'update' => 'Update Transfer',
        'receive_payment' => 'Receive Payment',

        'view_proof' => 'View Proof',

        'execute' => 'Execute Transfer',

        'cancel_transfer' => 'Cancel Transfer',

        'download' => 'Download',

        'close' => 'Close',
        'edit' => 'Edit',
    ],

    'messages' => [
        'created' => 'Transfer created successfully.',
        'updated' => 'Transfer updated successfully.',
        'completed' => 'Transfer completed successfully.',

        'cancelled' => 'Transfer cancelled successfully.',
    ],

    'errors' => [
        'calculation_failed' => 'Unable to calculate the transfer.',
        'only_pending_unpaid' => 'Only pending unpaid transfers can be updated.',
        'cannot_execute' => 'This transfer cannot be executed in its current state.',

        'cannot_cancel' => 'This transfer cannot be cancelled in its current state.',
    ],
    'table' => [

        'title' => 'Transfers',
        'description' => 'All customer transfers.',

        'reference' => 'Reference',
        'receiver' => 'Receiver',
        'method' => 'Method',
        'receiver_gets' => 'Receiver Gets',
        'customer_pays' => 'Customer Pays',
        'commission' => 'Commission',
        'transfer_status' => 'Transfer Status',
        'payment_status' => 'Payment Status',
        'created_by' => 'Created By',
        'created' => 'Created',

    ],
    'filters' => [

        'showing' => 'Showing:',

        'transfers' => 'Transfers',

        'payments' => 'Payments',

        'clear' => 'Clear Filter',

    ],
    'empty_state' => [

        'title' => 'No transfers found',

        'description' => 'Create your first transfer.',

    ],
    'sections' => [

        'actions' => 'Actions',

        'receiver' => 'Receiver Information',

        'transfer' => 'Transfer Information',

        'payment' => 'Payment',

        'audit' => 'Audit',

        'execute_transfer' => 'Execute Transfer',

    ],
    'details' => [

        'reference' => 'Reference',

        'name' => 'Name',

        'method' => 'Method',

        'bank_account' => 'Bank Account',

        'wallet_number' => 'Wallet Number',

        'receiver_gets' => 'Receiver Gets',

        'customer_pays' => 'Customer Pays',

        'commission' => 'Commission',

        'fee_mode' => 'Fee Mode',

        'calculation_mode' => 'Calculation Mode',

        'paid' => 'Paid',

        'remaining' => 'Remaining',

        'created_by' => 'Created By',

        'created_at' => 'Created At',

        'completed_at' => 'Completed At',

        'cancelled_at' => 'Cancelled At',

    ],
    'modal' => [

        'proof_title' => 'Transfer Proof',

    ],

];
