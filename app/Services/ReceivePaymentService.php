<?php

namespace App\Services;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CapitalTransactionType;
use App\Enums\PaymentStatus;
use App\Enums\TransactionDirection;
use App\Enums\TransferStatus;
use App\Models\CapitalAccount;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class ReceivePaymentService
{
    public function __construct(
        protected CapitalTransferService $capitalTransferService,
    ) {
    }

    public function receive(
        Transfer $transfer,
        float $paymentAmount,
        ?string $notes = null,
    ): void {
        if ($transfer->payment_status === PaymentStatus::PAID) {
            throw new \RuntimeException(
                __('services.receive_payment.already_paid')
            );
        }
        if ($transfer->status === TransferStatus::CANCELLED) {
            throw new \RuntimeException(
                __('services.receive_payment.transfer_cancelled')
            );
        }

        if ($paymentAmount > $transfer->remaining_amount) {
            throw new \RuntimeException(
                __('services.receive_payment.payment_exceeds_remaining')
            );
        }

        DB::transaction(function () use ($transfer, $paymentAmount, $notes) {

            $uaeCapital = CapitalAccount::lockForUpdate()
                ->where('branch', Branch::UAE)
                ->where('account_type', CapitalAccountType::CAPITAL)
                ->where('currency', $transfer->customer_payable_currency)
                ->firstOrFail();

            $transfer->payments()->create([
                'amount' => $paymentAmount,
                'currency' => $transfer->customer_payable_currency,
                'received_by' => auth()->id(),
                'received_at' => now(),
                'notes' => $notes,
            ]);

            $before = $uaeCapital->balance;

            $uaeCapital->balance += $paymentAmount;

            $uaeCapital->save();

            $this->capitalTransferService->recordTransaction(
                account: $uaeCapital,
                direction: TransactionDirection::IN,
                transactionType: CapitalTransactionType::CUSTOMER_TRANSFER,
                amount: $paymentAmount,
                balanceBefore: $before,
                balanceAfter: $uaeCapital->balance,
                createdBy: auth()->id(),
                reference: $transfer,
            );

            $transfer->paid_amount += $paymentAmount;

            $transfer->remaining_amount =
                $transfer->customer_payable_amount - $transfer->paid_amount;

            $transfer->payment_status =
                round($transfer->remaining_amount, 2) <= 0
                    ? PaymentStatus::PAID
                    : PaymentStatus::PARTIALLY_PAID;

            $transfer->save();

        });
    }
}
