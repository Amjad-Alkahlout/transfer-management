<?php

return [

    'page' => [
        'title' => 'إنشاء حوالة جديدة',
        'update_title' => 'تعديل الحوالة',
        'description' => 'إنشاء حوالة عميل جديدة.',
        'index_title' => 'الحوالات',
        'index_description' => 'إدارة حوالات العملاء.',
        'show_title' => 'تفاصيل الحوالة',
    ],

    'receiver' => [
        'title' => 'بيانات المستلم',
        'description' => 'معلومات المستلم وطريقة الاستلام.',
    ],

    'transfer' => [
        'title' => 'بيانات الحوالة',
        'description' => 'إعدادات احتساب الحوالة.',
    ],

    'payment' => [
        'title' => 'الدفعة',
        'description' => 'تسجيل دفعة اختيارية أثناء إنشاء الحوالة.',
    ],

    'additional' => [
        'title' => 'معلومات إضافية',
    ],

    'preview' => [
        'title' => 'معاينة الاحتساب',
        'description' => 'نتيجة الاحتساب حسب القيم الحالية.',

        'receiver_gets' => 'المستلم يستلم',
        'customer_pays' => 'العميل يدفع',
        'commission' => 'العمولة',
    ],

    'fields' => [
        'calculation_mode' => 'طريقة الاحتساب',
        'receiver_name' => 'اسم المستلم',
        'receiver_method' => 'طريقة الاستلام',

        'receiver_account_number' => 'رقم الحساب البنكي',
        'receiver_wallet_number' => 'رقم المحفظة',
        'requested_currency' => 'عملة المستلم',
        'requested_amount' => 'المبلغ المطلوب',
        'receiver_amount' => 'مبلغ المستلم',
        'fee_mode' => 'طريقة احتساب العمولة',
        'customer_pay_amount' => 'المبلغ الذي سيدفعه العميل',
        'customer_pay_currency' => 'عملة الدفع',
        'customer_payable_currency' => 'عملة المبلغ المستحق',
        'initial_payment' => 'الدفعة الأولى',
        'notes' => 'الملاحظات',
        'transfer_proof' => 'إثبات التحويل',
    ],

    'placeholders' => [
        'select_receiver_method' => 'اختر طريقة الاستلام',
        'select_requested_currency' => 'اختر عملة المستلم',
        'select_fee_mode' => 'اختر طريقة احتساب العمولة',
        'select_customer_pay_currency' => 'اختر عملة الدفع',
    ],

    'buttons' => [
        'back' => 'رجوع',
        'cancel' => 'إلغاء',
        'create' => 'إنشاء الحوالة',
        'update' => 'تحديث الحوالة',
        'receive_payment' => 'استلام دفعة',

        'view_proof' => 'عرض اثبات التحويل',

        'edit' => 'تعديل',

        'execute' => 'تنفيذ الحوالة',

        'cancel_transfer' => 'إلغاء الحوالة',

        'download' => 'تنزيل',

        'close' => 'إغلاق',


    ],

    'messages' => [
        'created' => 'تم إنشاء الحوالة بنجاح.',
        'updated' => 'تم تحديث الحوالة بنجاح.',
        'completed' => 'تم تنفيذ الحوالة بنجاح.',

        'cancelled' => 'تم إلغاء الحوالة بنجاح.',
    ],

    'errors' => [
        'calculation_failed' => 'تعذر احتساب الحوالة.',
        'only_pending_unpaid' => 'يمكن تعديل الحوالات المعلقة وغير المدفوعة فقط.',

        'cannot_execute' => 'لا يمكن تنفيذ هذه الحوالة بحالتها الحالية.',

        'cannot_cancel' => 'لا يمكن إلغاء هذه الحوالة بحالتها الحالية.',
    ],
    'table' => [

        'title' => 'الحوالات',
        'description' => 'جميع حوالات العملاء.',

        'reference' => 'المرجع',
        'receiver' => 'المستلم',
        'method' => 'طريقة الاستلام',
        'receiver_gets' => 'المستلم يستلم',
        'customer_pays' => 'العميل يدفع',
        'commission' => 'العمولة',
        'transfer_status' => 'حالة الحوالة',
        'payment_status' => 'حالة الدفع',
        'created_by' => 'أنشئت بواسطة',
        'created' => 'تاريخ الإنشاء',

    ],
    'filters' => [

        'showing' => 'عرض:',

        'transfers' => 'حوالات',

        'payments' => 'دفعات',

        'clear' => 'إزالة التصفية',

    ],
    'empty_state' => [

        'title' => 'لا توجد حوالات',

        'description' => 'أنشئ أول حوالة.',

    ],
    'sections' => [

        'actions' => 'الإجراءات',

        'receiver' => 'بيانات المستلم',

        'transfer' => 'بيانات الحوالة',

        'payment' => 'الدفعة',

        'audit' => 'سجل العمليات',

        'execute_transfer' => 'تنفيذ الحوالة',

    ],
    'details' => [

        'reference' => 'المرجع',

        'name' => 'الاسم',

        'method' => 'طريقة الاستلام',

        'bank_account' => 'رقم الحساب البنكي',

        'wallet_number' => 'رقم المحفظة',

        'receiver_gets' => 'المستلم يستلم',

        'customer_pays' => 'العميل يدفع',

        'commission' => 'العمولة',

        'fee_mode' => 'طريقة احتساب العمولة',

        'calculation_mode' => 'طريقة الاحتساب',

        'paid' => 'المدفوع',

        'remaining' => 'المتبقي',

        'created_by' => 'أنشئت بواسطة',

        'created_at' => 'تاريخ الإنشاء',

        'completed_at' => 'تاريخ التنفيذ',

        'cancelled_at' => 'تاريخ الإلغاء',

    ],
    'modal' => [

        'proof_title' => 'إثبات التحويل',

    ],

];
