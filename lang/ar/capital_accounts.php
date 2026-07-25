<?php

return [

    'page' => [
        'title' => 'حسابات رأس المال',
        'description' => 'إدارة حسابات رأس مال الشركة.',
    ],

    'table' => [
        'title' => 'حسابات رأس المال',
        'description' => 'جميع حسابات رأس المال المضافة.',

        'name' => 'الاسم',
        'branch' => 'الفرع',
        'currency' => 'العملة',
        'current_balance' => 'الرصيد الحالي',
        'account_type' => 'نوع الحساب',
        'is_active' => 'الحالة',
        'actions' => 'الإجراءات',
    ],

    'forms' => [

        'add' => [
            'title' => 'إضافة حساب رأس مال',
            'description' => 'إنشاء حساب رأس مال جديد.',
        ],

        'edit' => [
            'title' => 'تعديل حساب رأس المال',
            'description' => 'تحديث بيانات الحساب.',
        ],

        'withdraw' => [
            'title' => 'سحب الأرباح',
            'description' => 'سحب الأرباح من الحساب :account',
        ],
        'adjustment' => [
            'title' => 'تعديل يدوي على الرصيد',
            'description' => 'تعديل رصيد الحساب :account يدوياً (إضافة أو خصم)، مع توثيق السبب.',
        ],
        'profit_distribution' => [
            'title' => 'توزيع أرباح إلى الإمارات',
            'description' => 'تحويل جزء من رصيد حساب الربح (:account) مباشرة إلى حساب رأس مال في الإمارات، دون أي تكلفة تحويل.',
        ],

    ],

    'fields' => [
        'name' => 'الاسم',
        'branch' => 'الفرع',
        'currency' => 'العملة',
        'account_type' => 'نوع الحساب',
        'opening_balance' => 'الرصيد الافتتاحي',
        'amount' => 'المبلغ',
        'notes' => 'الملاحظات',
        'adjustment_direction' => 'نوع التعديل',
        'adjustment_notes' => 'سبب التعديل',
        'destination_account' => 'الحساب المستلم (الإمارات)',
    ],

    'buttons' => [
        'back' => 'لوحة التحكم',
        'add' => 'إضافة حساب رأس مال',
        'edit' => 'تعديل',
        'update' => 'تحديث حساب رأس المال',
        'activate' => 'تفعيل',
        'deactivate' => 'تعطيل',
        'withdraw_profit' => 'سحب الأرباح',
        'adjust_balance' => 'تعديل الرصيد',
        'save_adjustment' => 'حفظ التعديل',
        'distribute_profit' => 'توزيع للإمارات',
    ],

    'status' => [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
    ],

    'messages' => [
        'created' => 'تمت إضافة حساب رأس المال بنجاح.',
        'updated' => 'تم تحديث حساب رأس المال بنجاح.',
        'activated' => 'تم تفعيل حساب رأس المال بنجاح.',
        'deactivated' => 'تم تعطيل حساب رأس المال بنجاح.',
        'profit_withdrawn' => 'تم سحب الأرباح بنجاح.',
        'profit_distributed' => 'تم توزيع الأرباح بنجاح.',
    ],

    'errors' => [
        'profit_branch_only' => 'يمكن إنشاء حسابات الأرباح لفرع غزة فقط.',
        'duplicate_account' => 'يوجد حساب بنفس الفرع والعملة ونوع الحساب.',
    ],

    'placeholders' => [
        'select_branch' => 'اختر الفرع',
        'select_currency' => 'اختر العملة',
        'select_account_type' => 'اختر نوع الحساب',
        'select_uae_account' => 'اختر حساب الإمارات',
    ],
    'adjustment_direction' => [
        'add' => 'إضافة مبلغ',
        'deduct' => 'خصم مبلغ',
    ],

];
