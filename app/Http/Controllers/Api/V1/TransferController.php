<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TransferIndexRequest;
use App\Http\Resources\Api\V1\TransferResource;
use App\Models\Transfer;
use App\Enums\TransferStatus;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransferController extends Controller
{
    public function index(TransferIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $query = Transfer::query()->with('creator');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status'])
                ->where('status', '!=', TransferStatus::CANCELLED->value);
        }

        if (! empty($filters['reference'])) {
            $query->where('reference_number', 'like', '%'.$filters['reference'].'%');
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        $transfers = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return TransferResource::collection($transfers);
    }

    public function show(Transfer $transfer): TransferResource
    {
        $transfer->load([
            'creator',
            'payments.receiver',
        ]);

        return TransferResource::make($transfer);
    }
}
