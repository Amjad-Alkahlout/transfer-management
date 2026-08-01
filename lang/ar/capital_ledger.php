<?php

return [

    'page' => [
        'title' => 'سجل رأس المال',
        'description' => 'عرض وتصفية جميع حركات حسابات رأس المال.',
    ],

    'sections' => [
        'filters' => 'التصفية',
        'filters_description' => 'قم بتصفية حركات رأس المال باستخدام الخيارات التالية.',
        'transactions' => 'حركات رأس المال',
        'transactions_description' => 'عرض جميع الحركات المالية الخاصة بحسابات رأس المال.',
    ],

    'filters' => [
        'account' => 'الحساب',
        'all_accounts' => 'جميع الحسابات',

        'transaction_type' => 'نوع العملية',
        'all_transaction_types' => 'جميع أنواع العمليات',

        'from_date' => 'من تاريخ',
        'to_date' => 'إلى تاريخ',
    ],

    'table' => [
        'account' => 'الحساب',
        'amount' => 'المبلغ',
        'direction' => 'الاتجاه',
        'balance_before' => 'الرصيد قبل',
        'balance_after' => 'الرصيد بعد',
        'transaction_type' => 'نوع العملية',
        'description' => 'الوصف',
        'date' => 'التاريخ',
    ],

    'messages' => [
        'empty' => 'لا توجد حركات لرأس المال.',
    ],

];
