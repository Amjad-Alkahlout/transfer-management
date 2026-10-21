<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'currency' => $this->currency->value,
            'notes' => $this->notes,
            'received_at' => $this->received_at?->toIso8601String(),
            'received_by' => $this->whenLoaded('receiver', fn () => $this->receiver ? [
                'id' => $this->receiver->id,
                'name' => $this->receiver->name,
            ] : null),
        ];
    }
}
