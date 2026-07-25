<?php

return [

    'page' => [
        'title' => 'Commission Rules',
        'description' => 'Manage commission rules by currency.',
    ],
    'filter' => [
        'title' => 'Currency Filter',
    ],
    'table' => [

        'title' => 'Commission Rules',

        'description' => 'Configured commission ranges.',

        'from' => 'From',

        'to' => 'To',

        'commission' => 'Commission (AED)',

        'actions' => 'Actions',

    ],
    'form' => [

        'title' => 'Add Commission Rule',

        'description' => 'Create a new commission range.',

    ],
    'fields' => [

        'currency' => 'Currency',

        'from' => 'From',

        'to' => 'To',

        'commission' => 'Commission (AED)',

    ],
    'buttons' => [

        'add' => 'Add Rule',

        'save' => 'Save Rule',

        'delete' => 'Delete',
        'cancel' => 'Cancel',
        'back' => 'Back',

    ],
    'empty_state' => [

        'title' => 'No commission rules',

        'description' => 'Create your first commission rule.',

    ],
    'confirmations' => [

        'delete' => 'Delete this commission rule?',

    ],
    'messages' => [

        'created' => 'Commission rule added successfully.',

        'deleted' => 'Commission rule deleted successfully.',

    ],
    'errors' => [

        'overlap' => 'This range overlaps with an existing commission rule.',

    ],
];
