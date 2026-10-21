<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CapitalAccountType;
use App\Enums\TransactionDirection;
use App\Http\Controllers\Controller;
use App\Models\CapitalAccount;
use App\Services\CapitalAccountAdjustmentService;
use App\Services\CapitalAccountManagementService;
use App\Services\ProfitWithdrawalService;
use App\Services\ApiIdempotencyService;
use App\Exceptions\Api\IdempotencyConflictException;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class CapitalAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $accounts = CapitalAccount::query()->orderBy('branch')->orderBy('currency')->paginate(min((int) $request->query('per_page', 20), 100));
        return response()->json(['data' => $accounts->getCollection()->map(fn (CapitalAccount $a) => $this->serialize($a)), 'meta' => ['current_page' => $accounts->currentPage(), 'per_page' => $accounts->perPage(), 'total' => $accounts->total(), 'last_page' => $accounts->lastPage()]]);
    }

    public function store(Request $request, CapitalAccountManagementService $service): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'branch' => ['required', Rule::enum(\App\Enums\Branch::class)],
            'currency' => ['required', Rule::enum(\App\Enums\CurrencyType::class)], 'account_type' => ['required', Rule::enum(CapitalAccountType::class)],
            'opening_balance' => ['sometimes', 'numeric', 'decimal:0,2', 'min:0'], 'is_active' => ['sometimes', 'boolean'], 'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $account = $service->create($data, (int) $request->user()->id);
        return response()->json(['data' => $this->serialize($account)], 201);
    }

    public function update(Request $request, CapitalAccount $account, CapitalAccountManagementService $service): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'], 'branch' => ['sometimes', Rule::enum(\App\Enums\Branch::class)],
            'currency' => ['sometimes', Rule::enum(\App\Enums\CurrencyType::class)], 'account_type' => ['sometimes', Rule::enum(CapitalAccountType::class)],
            'is_active' => ['sometimes', 'boolean'], 'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);
        return response()->json(['data' => $this->serialize($service->update($account, $data))]);
    }

    public function adjust(Request $request, CapitalAccount $account, CapitalAccountAdjustmentService $service): JsonResponse
    {
        $data = $request->validate(['direction' => ['required', Rule::enum(TransactionDirection::class)], 'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'], 'notes' => ['required', 'string', 'max:255']]);
        return $this->idempotent($request, "capital-adjustment:{$account->id}", $data, function () use ($request, $account, $service, $data): array {
            try {
                $service->adjust($account, TransactionDirection::from($data['direction']), (float) $data['amount'], $data['notes'], (int) $request->user()->id);
            } catch (RuntimeException $exception) {
                return ['status' => 409, 'body' => ['message' => $exception->getMessage(), 'error' => ['code' => 'CAPITAL_ADJUSTMENT_NOT_ALLOWED']]];
            }
            return ['status' => 200, 'body' => ['data' => $this->serialize($account->refresh())]];
        });
    }

    public function withdraw(Request $request, CapitalAccount $account, ProfitWithdrawalService $service): JsonResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'], 'notes' => ['nullable', 'string', 'max:255']]);
        return $this->idempotent($request, "profit-withdrawal:{$account->id}", $data, function () use ($request, $account, $service, $data): array {
            try {
                $service->withdraw($account, (float) $data['amount'], (int) $request->user()->id, $data['notes'] ?? null);
            } catch (RuntimeException $exception) {
                return ['status' => 409, 'body' => ['message' => $exception->getMessage(), 'error' => ['code' => 'PROFIT_WITHDRAWAL_NOT_ALLOWED']]];
            }
            return ['status' => 200, 'body' => ['data' => $this->serialize($account->refresh())]];
        });
    }

    private function idempotent(Request $request, string $action, array $payload, Closure $operation): JsonResponse
    {
        $key = $request->header('Idempotency-Key');
        validator(['idempotency_key' => $key], ['idempotency_key' => ['required', 'string', 'max:255']])->validate();
        try {
            $result = app(ApiIdempotencyService::class)->execute($request->user(), $key, $action, $payload, $operation);
        } catch (IdempotencyConflictException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'error' => ['code' => $exception->errorCode]], 409);
        }
        return response()->json($result['body'], $result['status']);
    }

    private function serialize(CapitalAccount $account): array
    {
        return ['id' => $account->id, 'name' => $account->name, 'branch' => $account->branch->value, 'currency' => $account->currency->value,
            'account_type' => $account->account_type->value, 'balance' => (string) $account->balance, 'is_active' => $account->is_active, 'notes' => $account->notes];
    }
}
