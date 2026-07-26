<?php

return [

    'branch' => [
        'uae' => 'الإمارات',
        'gaza' => 'غزة',
    ],

    'capital_account_type' => [
        'capital' => 'رأس المال',
        'profit' => 'الأرباح',
    ],

    'capital_transaction_type' => [
        'customer_transfer' => 'حوالة عميل',
        'internal_transfer' => 'تحويل داخلي',
        'manual_adjustment' => 'تعديل يدوي',
        'opening_balance' => 'الرصيد الافتتاحي',
        'transfer_expense' => 'مصروف حوالة',
        'profit_withdrawal' => 'سحب أرباح',
        'transfer_cancellation' => 'إلغاء حوالة',
        'profit_distribution' => 'توزيع أرباح',
    ],

    'currency' => [
        'aed' => 'درهم إماراتي',
        'usd' => 'دولار أمريكي',
        'ils' => 'شيكل',
    ],

    'fee_mode' => [
        'included' => 'شامل',
        'excluded' => 'غير شامل',
    ],

    'payment_status' => [
        'unpaid' => 'غير مدفوع',
        'partially_paid' => 'مدفوع جزئياً',
        'paid' => 'مدفوع',
    ],

    'receiver_method' => [
        'bank' => 'حساب بنكي',
        'wallet' => 'محفظة إلكترونية',
    ],

    'transaction_direction' => [
        'in' => 'وارد',
        'out' => 'صادر',
    ],

    'transfer_calculation_mode' => [
        'receiver_amount' => 'المبلغ الذي يريد استلامه',
        'customer_payment' => 'المبلغ الذي يريد دفعه',
    ],

    'transfer_status' => [
        'pending' => 'قيد الانتظار',
        'completed' => 'مكتملة',
        'cancelled' => 'ملغاة',
    ],

    'user_role' => [
        'admin' => 'مدير النظام',
        'coordinator' => 'منسق',
        'executor' => 'منفذ',
        'transfer_executor' => 'منفذ الحوالات',

    ],

];
