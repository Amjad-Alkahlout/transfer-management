<?php

return [

    'page' => [
        'title' => 'Receive Payment',
    ],

    'summary' => [
        'title' => 'Payment Summary',
        'description' => 'Current payment status for this transfer.',

        'requested_amount' => 'Requested Amount',
        'paid' => 'Paid',
        'remaining' => 'Remaining',
    ],

    'form' => [
        'title' => 'Receive Payment',
        'description' => 'Record a customer payment.',
    ],

    'history' => [
        'title' => 'Payment History',
        'description' => 'All recorded payments for this transfer.',

        'amount' => 'Amount',
        'received_by' => 'Received By',
        'date' => 'Date',
        'notes' => 'Notes',
    ],

    'fields' => [
        'payment_amount' => 'Payment Amount',
        'notes' => 'Notes',
    ],

    'buttons' => [
        'receive' => 'Receive Payment',
        'cancel' => 'Cancel',
        'back' => 'Back',
    ],

    'empty_state' => [
        'title' => 'No payments recorded',
        'description' => 'Payments will appear here after they are received.',
    ],

    'messages' => [
        'received' => 'Payment received successfully.',
    ],

];
