<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CapitalAccount;
use App\Models\CapitalTransfer;
use App\Exceptions\Api\IdempotencyConflictException;
use App\Services\ApiIdempotencyService;
use App\Services\CapitalTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CapitalTransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = CapitalTransfer::query()->with(['fromAccount', 'toAccount'])->latest()->paginate(min((int) $request->query('per_page', 20), 100));
        return response()->json(['data' => $items->getCollection()->map(fn (CapitalTransfer $t) => $this->serialize($t)), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total(), 'last_page' => $items->lastPage()]]);
    }

    public function preview(Request $request, CapitalTransferService $service): JsonResponse
    {
        $data = $request->validate(['from_account_id' => ['required', 'integer', 'exists:capital_accounts,id'], 'to_account_id' => ['required', 'integer', 'different:from_account_id', 'exists:capital_accounts,id'], 'source_amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'], 'transfer_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0']]);
        $result = $service->preview(CapitalAccount::findOrFail($data['from_account_id']), CapitalAccount::findOrFail($data['to_account_id']), (float) $data['source_amount'], (float) $data['transfer_cost']);
        return response()->json(['data' => ['exchange_rate' => (string) $result['exchange_rate'], 'destination_amount' => number_format($result['destination_amount'], 2, '.', ''), 'total_deduction' => number_format($result['total_deduction'], 2, '.', '')]]);
    }

    public function store(Request $request, CapitalTransferService $service): JsonResponse
    {
        $data = $request->validate(['from_account_id' => ['required', 'integer', 'exists:capital_accounts,id'], 'to_account_id' => ['required', 'integer', 'different:from_account_id', 'exists:capital_accounts,id'], 'source_amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'], 'transfer_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0'], 'notes' => ['nullable', 'string', 'max:255']]);
        $key = $request->header('Idempotency-Key');
        validator(['idempotency_key' => $key], ['idempotency_key' => ['required', 'string', 'max:255']])->validate();
        try {
            $result = app(ApiIdempotencyService::class)->execute($request->user(), $key, 'capital-transfer', $data, function () use ($request, $service, $data): array {
                $transfer = $service->transfer(CapitalAccount::findOrFail($data['from_account_id']), CapitalAccount::findOrFail($data['to_account_id']), (float) $data['source_amount'], (float) $data['transfer_cost'], (int) $request->user()->id, $data['notes'] ?? null);
                return ['status' => 201, 'body' => ['data' => $this->serialize($transfer->load(['fromAccount', 'toAccount']))]];
            });
        } catch (IdempotencyConflictException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'error' => ['code' => $exception->errorCode]], 409);
        }
        return response()->json($result['body'], $result['status']);
    }

    private function serialize(CapitalTransfer $t): array
    {
        return ['id' => $t->id, 'from_account' => ['id' => $t->fromAccount->id, 'name' => $t->fromAccount->name, 'currency' => $t->fromAccount->currency->value],
            'to_account' => ['id' => $t->toAccount->id, 'name' => $t->toAccount->name, 'currency' => $t->toAccount->currency->value],
            'source_amount' => (string) $t->source_amount, 'destination_amount' => (string) $t->destination_amount, 'transfer_cost' => (string) $t->transfer_cost, 'exchange_rate' => (string) $t->exchange_rate, 'notes' => $t->notes, 'created_at' => $t->created_at?->toIso8601String()];
    }
}
