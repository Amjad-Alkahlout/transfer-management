<?php

namespace App\Http\Requests\Api\V1;

class UpdateTransferRequest extends TransferMutationRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update-transfer') ?? false;
    }

    public function rules(): array
    {
        return $this->transferRules(includeInitialPayment: false);
    }
}
