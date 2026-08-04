<?php

return [

    'capital_transfer' => [

        'same_account' => 'لا يمكن أن يكون الحساب المصدر والوجهة هو نفس الحساب.',

        'source_must_be_capital' => 'يجب أن يكون الحساب المصدر من نوع رأس مال.',

        'destination_must_be_capital' => 'يجب أن يكون الحساب الوجهة من نوع رأس مال.',

        'source_inactive' => 'الحساب المصدر غير نشط.',

        'destination_inactive' => 'الحساب الوجهة غير نشط.',

        'insufficient_source_balance' => 'الرصيد غير كافٍ في الحساب المصدر.',

        'amount_must_be_positive' => 'يجب أن يكون مبلغ التحويل أكبر من صفر.',

        'cost_cannot_be_negative' => 'لا يمكن أن تكون تكلفة التحويل سالبة.',

        'profit_account_not_found' => 'لا يوجد حساب أرباح للعملة :currency.',

        'invalid_destination_amount' => 'المبلغ المحول إلى الحساب الوجهة غير صالح.',

        'insufficient_profit_balance' => 'الرصيد غير كافٍ في حساب أرباح غزة.',
        'profit_distribution_no_cost' => 'عملية توزيع الأرباح لا تقبل إضافة تكلفة تحويل.',

    ],
    'commission' => [

        'rule_not_found' => 'لا توجد قاعدة عمولة لهذا المبلغ.',

    ],
    'currency_converter' => [

        'exchange_rate_not_found' => 'لم يتم العثور على سعر الصرف.',

    ],
    'profit_withdrawal' => [

        'invalid_account' => 'الحساب المحدد ليس حساب أرباح.',

        'inactive_account' => 'حساب الأرباح غير نشط.',

        'amount_must_be_positive' => 'يجب أن يكون المبلغ أكبر من صفر.',

        'insufficient_balance' => 'الرصيد غير كافٍ.',

    ],
    'receive_payment' => [

        'already_paid' => 'تم سداد هذه الحوالة بالكامل مسبقًا.',

        'transfer_cancelled' => 'تم إلغاء هذه الحوالة، ولا يمكن استلام أي دفعات إضافية.',

        'payment_exceeds_remaining' => 'لا يمكن أن تتجاوز الدفعة الرصيد المتبقي.',

    ],
    'transfer_calculator' => [

        'included_fee_exceeds_amount' => 'عمولة هذا المبلغ أكبر من قيمة المبلغ نفسه. الرجاء إدخال مبلغ أكبر أو تغيير طريقة احتساب العمولة إلى "غير مشمولة".',

        'fee_exceeds_amount' => 'عمولة هذا المبلغ أكبر من قيمة المبلغ نفسه.',

    ],
    'transfer_cancellation' => [

        'cannot_cancel' => 'لا يمكن إلغاء هذه الحوالة.',

    ],
    'transfer_execution' => [

        'insufficient_gaza_capital' => 'الرصيد غير كافٍ في حساب رأس المال - غزة.',

    ],
    'capital_account_adjustment' => [
        'inactive_account' => 'لا يمكن تعديل رصيد حساب غير نشط.',
        'amount_must_be_positive' => 'يجب أن يكون المبلغ أكبر من صفر.',
        'insufficient_balance' => 'الرصيد الحالي غير كافٍ لإتمام عملية الخصم.',
    ],
    'force_cancel' => [
        'only_completed_allowed' => 'يمكن إلغاء الحوالات المكتملة فقط بهذه الطريقة.',
    ],

];
