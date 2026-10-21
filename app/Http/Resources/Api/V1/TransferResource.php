<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'receiver' => [
                'name' => $this->receiver_name,
                'method' => $this->receiver_method?->value,
                'account_number' => $this->receiver_account_number,
                'wallet_phone' => $this->receiver_wallet_phone,
            ],
            'payout' => [
                'amount' => number_format((float) $this->transfer_amount, 2, '.', ''),
                'currency' => $this->requested_currency?->value,
            ],
            'customer_payment' => [
                'payable_amount' => number_format((float) $this->customer_payable_amount, 2, '.', ''),
                'currency' => $this->customer_payable_currency?->value,
                'paid_amount' => number_format((float) $this->paid_amount, 2, '.', ''),
                'remaining_amount' => number_format((float) $this->remaining_amount, 2, '.', ''),
                'status' => $this->payment_status?->value,
            ],
            'commission' => [
                'amount' => number_format((float) $this->commission_amount, 2, '.', ''),
                'currency' => $this->commission_currency?->value,
            ],
            'calculation_mode' => $this->calculation_mode?->value,
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'proof_available' => filled($this->transfer_proof_path),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
