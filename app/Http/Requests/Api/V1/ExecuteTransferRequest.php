<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('execute-transfer') ?? false;
    }

    public function rules(): array
    {
        return [
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'mimetypes:image/jpeg,image/png,application/pdf', 'max:2048'],
        ];
    }
}
