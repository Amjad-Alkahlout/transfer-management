<?php

return [

    'page' => [
        'title' => 'لوحة التحكم',
        'description' => 'نظرة عامة على نظام إدارة الحوالات المالية',
    ],

    'financial' => [
        'title' => 'النظرة المالية',
        'description' => 'الأرصدة الحالية لجميع حسابات الشركة.',

        'gaza_capital' => 'رأس مال غزة',
        'uae_capital_usd' => 'رأس مال الإمارات (دولار)',
        'uae_capital_aed' => 'رأس مال الإمارات (درهم)',
        'profit' => 'الأرباح',
        'customer_receivables' => 'ذمم العملاء (درهم)',

        'current_balance' => 'الرصيد الحالي',
        'net_profit' => 'صافي الأرباح',
        'outstanding_amount' => 'المبلغ المستحق',
    ],

    'quick_actions' => [
        'title' => 'إجراءات سريعة',

        'create_transfer' => 'إنشاء حوالة',

        'transfers' => 'الحوالات',
        'capital_transfers' => 'تحويلات رأس المال',
        'capital_accounts' => 'حسابات رأس المال',
        'exchange_rates' => 'أسعار الصرف',
        'commission_rules' => 'قواعد العمولات',
    ],

    'transfers' => [
        'title' => 'ملخص الحوالات',
        'description' => 'ملخص حالات الحوالات الحالية.',

        'status' => 'الحالة',
        'total' => 'الإجمالي',
    ],

    'payments' => [
        'title' => 'ملخص الدفعات',
        'description' => 'ملخص حالات الدفع الحالية.',

        'status' => 'الحالة',
        'total' => 'الإجمالي',
    ],

    'exchange_rates' => [
        'title' => 'أسعار الصرف',
        'description' => 'أسعار الصرف الحالية.',

        'currency' => 'العملة',
        'rate_to_usd' => 'السعر مقابل الدولار',
    ],

    'commission_rules' => [
        'title' => 'قواعد العمولات',
        'description' => 'قواعد العمولات المضافة.',

        'currency' => 'العملة',
        'total_rules' => 'عدد القواعد',
    ],

    'telegram' => [

        'connected' => 'تيليجرام',

        'link' => 'ربط تيليجرام',

        'modal_title' => 'إشعارات تيليجرام',

        'connected_title' => 'تم ربط تيليجرام',

        'connected_description' => 'حساب تيليجرام الخاص بك مرتبط وسيستقبل إشعارات الحوالات.',

        'status' => 'الحالة',

        'enabled' => 'مفعل',

        'disconnect' => 'إلغاء الربط',

        'relink' => 'إعادة الربط',

        'close' => 'إغلاق',

        'link_title' => 'ربط تيليجرام',

        'link_description' => 'اربط حساب تيليجرام الخاص بك لاستلام إشعارات فورية عند وجود حوالة تحتاج إلى إجراء.',

        'step_1' => 'الخطوة الأولى',

        'step_2' => 'الخطوة الثانية',

        'open_bot' => 'فتح البوت',

        'open_bot_description' => 'افتح بوت تيليجرام الخاص بنا.',

        'send_command' => 'أرسل الأمر التالي:',

        'copy_command' => 'نسخ الأمر',

        'code_expire' => 'تنتهي صلاحية هذا الرمز بعد 10 دقائق.',
    ],

    'messages' => [
        'telegram_linked' => 'تم ربط تيليجرام بنجاح.',
        'telegram_disconnected' => 'تم إلغاء ربط تيليجرام بنجاح.',
    ],

];
