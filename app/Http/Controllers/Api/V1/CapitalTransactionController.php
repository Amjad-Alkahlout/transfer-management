<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CapitalTransactionType;
use App\Http\Controllers\Controller;
use App\Models\CapitalTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CapitalTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'account_id' => ['sometimes', 'integer', 'exists:capital_accounts,id'],
            'type' => ['sometimes', Rule::enum(CapitalTransactionType::class)],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date', 'after_or_equal:from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $items = CapitalTransaction::query()->with(['account:id,name,currency', 'creator:id,name'])
            ->when(isset($filters['account_id']), fn ($q) => $q->where('capital_account_id', $filters['account_id']))
            ->when(isset($filters['type']), fn ($q) => $q->where('transaction_type', $filters['type']))
            ->when(isset($filters['from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(isset($filters['to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20);

        return response()->json(['data' => $items->getCollection()->map(fn (CapitalTransaction $t) => ['id' => $t->id, 'account' => ['id' => $t->account->id, 'name' => $t->account->name, 'currency' => $t->account->currency->value], 'amount' => (string) $t->amount, 'direction' => $t->direction->value, 'type' => $t->transaction_type->value, 'balance_before' => (string) $t->balance_before, 'balance_after' => (string) $t->balance_after, 'notes' => $t->notes, 'created_by' => $t->creator?->name, 'created_at' => $t->created_at?->toIso8601String()]), 'meta' => ['current_page' => $items->currentPage(), 'per_page' => $items->perPage(), 'total' => $items->total(), 'last_page' => $items->lastPage()]]);
    }
}
