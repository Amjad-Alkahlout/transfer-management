<?php

namespace App\Http\Requests\Api\V1;

class StoreTransferRequest extends TransferMutationRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create-transfer') ?? false;
    }

    public function rules(): array
    {
        return $this->transferRules(includeInitialPayment: true);
    }
}
