<?php

namespace App\Services;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CapitalTransactionType;
use App\Enums\TransactionDirection;
use App\Models\CapitalAccount;
use App\Models\CapitalTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CapitalAccountManagementService
{
    public function create(array $data, int $createdBy): CapitalAccount
    {
        if (($data['account_type'] ?? null) === CapitalAccountType::PROFIT->value && ($data['branch'] ?? null) !== Branch::GAZA->value) {
            throw ValidationException::withMessages(['account_type' => 'Profit accounts are only allowed in Gaza.']);
        }

        return DB::transaction(function () use ($data, $createdBy): CapitalAccount {
            if (CapitalAccount::query()->where('branch', $data['branch'])->where('currency', $data['currency'])->where('account_type', $data['account_type'])->exists()) {
                throw ValidationException::withMessages(['currency' => 'An account already exists for this branch, currency, and type.']);
            }
            $account = CapitalAccount::create([
                'name' => $data['name'], 'branch' => $data['branch'], 'currency' => $data['currency'],
                'account_type' => $data['account_type'], 'balance' => $data['opening_balance'] ?? '0.00',
                'is_active' => $data['is_active'] ?? true, 'notes' => $data['notes'] ?? null,
            ]);

            if ((float) ($data['opening_balance'] ?? 0) > 0) {
                app(CapitalTransferService::class)->recordTransaction(
                    account: $account, direction: TransactionDirection::IN,
                    transactionType: CapitalTransactionType::OPENING_BALANCE,
                    amount: (float) $data['opening_balance'], balanceBefore: 0,
                    balanceAfter: (float) $data['opening_balance'], createdBy: $createdBy,
                    notes: 'Initial opening balance',
                );
            }

            return $account;
        });
    }

    public function update(CapitalAccount $account, array $data): CapitalAccount
    {
        if (($data['account_type'] ?? $account->account_type->value) === CapitalAccountType::PROFIT->value
            && ($data['branch'] ?? $account->branch->value) !== Branch::GAZA->value) {
            throw ValidationException::withMessages(['account_type' => 'Profit accounts are only allowed in Gaza.']);
        }

        $branch = $data['branch'] ?? $account->branch->value;
        $currency = $data['currency'] ?? $account->currency->value;
        $type = $data['account_type'] ?? $account->account_type->value;
        if (CapitalAccount::query()->where('branch', $branch)->where('currency', $currency)->where('account_type', $type)->where('id', '!=', $account->id)->exists()) {
            throw ValidationException::withMessages(['currency' => 'An account already exists for this branch, currency, and type.']);
        }

        $account->fill(array_intersect_key($data, array_flip(['name', 'branch', 'currency', 'account_type', 'is_active', 'notes'])))->save();
        return $account->refresh();
    }
}
