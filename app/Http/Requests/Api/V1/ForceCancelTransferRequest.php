<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ForceCancelTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('force-cancel-completed-transfer') ?? false;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'min:1', 'max:255'],
        ];
    }
}
