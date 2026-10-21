<?php

namespace App\Services;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CapitalTransactionType;
use App\Enums\TransactionDirection;
use App\Models\CapitalAccount;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CapitalAccountManagementService
{
    public function create(array $data, int $createdBy): CapitalAccount
    {
        if (($data['account_type'] ?? null) === CapitalAccountType::PROFIT->value && ($data['branch'] ?? null) !== Branch::GAZA->value) {
            throw ValidationException::withMessages(['account_type' => 'Profit accounts are only allowed in Gaza.']);
        }

        try {
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
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? null;
            $driverCode = $exception->errorInfo[1] ?? null;
            $message = strtolower($exception->getMessage());
            $isUniqueViolation = $sqlState === '23505'
                || ($sqlState === '23000' && ($driverCode === 1062 || str_contains($message, 'unique constraint failed')));

            if ($isUniqueViolation) {
                throw ValidationException::withMessages(['currency' => 'An account already exists for this branch, currency, and type.']);
            }

            throw $exception;
        }
    }

    public function update(CapitalAccount $account, array $data): CapitalAccount
    {
        $account->fill(array_intersect_key($data, array_flip(['name', 'is_active', 'notes'])))->save();

        return $account->refresh();
    }
}
