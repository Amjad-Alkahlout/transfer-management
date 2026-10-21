<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CurrencyType;
use App\Enums\TransferCalculationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferCalculationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create-transfer') ?? false;
    }

    public function rules(): array
    {
        $receiverAmountMode = $this->input('calculation_mode') === TransferCalculationMode::RECEIVER_AMOUNT->value;
        $customerPaymentMode = $this->input('calculation_mode') === TransferCalculationMode::CUSTOMER_PAYMENT->value;

        return [
            'calculation_mode' => ['required', Rule::enum(TransferCalculationMode::class)],
            'requested_amount' => [Rule::requiredIf($receiverAmountMode), 'nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            'requested_currency' => ['required', Rule::enum(CurrencyType::class)],
            'customer_payable_amount' => [Rule::requiredIf($customerPaymentMode), 'nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            'customer_payable_currency' => ['required', Rule::enum(CurrencyType::class)],
            'commission_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }
}
