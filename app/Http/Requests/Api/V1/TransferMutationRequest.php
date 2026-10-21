<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\CurrencyType;
use App\Enums\ReceiverMethod;
use App\Enums\TransferCalculationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class TransferMutationRequest extends FormRequest
{
    protected function transferRules(bool $includeInitialPayment): array
    {
        $receiverAmountMode = $this->input('calculation_mode') === TransferCalculationMode::RECEIVER_AMOUNT->value;
        $customerPaymentMode = $this->input('calculation_mode') === TransferCalculationMode::CUSTOMER_PAYMENT->value;
        $isBank = $this->input('receiver_method') === ReceiverMethod::BANK->value;
        $isWallet = $this->input('receiver_method') === ReceiverMethod::WALLET->value;

        $rules = [
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_method' => ['required', Rule::enum(ReceiverMethod::class)],
            'receiver_account_number' => [Rule::requiredIf($isBank), 'nullable', 'string', 'max:20'],
            'receiver_wallet_phone' => [Rule::requiredIf($isWallet), 'nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:255'],
            'calculation_mode' => ['required', Rule::enum(TransferCalculationMode::class)],
            'requested_amount' => [Rule::requiredIf($receiverAmountMode), 'nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            'requested_currency' => ['required', Rule::enum(CurrencyType::class)],
            'customer_payable_amount' => [Rule::requiredIf($customerPaymentMode), 'nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            'customer_payable_currency' => ['required', Rule::enum(CurrencyType::class)],
            'commission_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];

        if ($includeInitialPayment) {
            $rules['initial_customer_pay_amount'] = ['sometimes', 'nullable', 'numeric', 'min:0', 'decimal:0,2'];
        }

        return $rules;
    }
}
