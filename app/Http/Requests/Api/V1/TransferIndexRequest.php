<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PaymentStatus;
use App\Enums\TransferStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view-transfers') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(TransferStatus::class)],
            'payment_status' => ['sometimes', 'nullable', Rule::enum(PaymentStatus::class)],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
            'created_from' => ['sometimes', 'nullable', 'date'],
            'created_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:created_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
