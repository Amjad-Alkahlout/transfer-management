<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\ReceiverMethod;
use App\Enums\TransferCalculationMode;
use App\Enums\TransferStatus;
use App\Events\TransferCreated;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use DomainException;

class TransferManagementService
{
    public function __construct(
        protected TransferCalculatorService $calculator,
        protected ReceivePaymentService $receivePaymentService,
    ) {
    }

    public function create(array $input, User $actor): Transfer
    {
        $mode = TransferCalculationMode::from($input['calculation_mode']);
        $calculation = $this->calculate($input, $mode);
        $initialPayment = (float) ($input['initial_customer_pay_amount'] ?? 0);

        if ((int) round($initialPayment * 100) > (int) round((float) $calculation['customer_payable_amount'] * 100)) {
            throw ValidationException::withMessages([
                'initial_customer_pay_amount' => 'The initial payment cannot exceed the payable amount.',
            ]);
        }

        $transfer = DB::transaction(function () use ($input, $actor, $mode, $calculation, $initialPayment): Transfer {
            $transfer = new Transfer();
            $transfer->forceFill([
                'reference_number' => $this->newReferenceNumber(),
                'receiver_name' => $input['receiver_name'],
                'receiver_method' => $input['receiver_method'],
                'receiver_account_number' => $input['receiver_method'] === ReceiverMethod::BANK->value
                    ? ($input['receiver_account_number'] ?? null)
                    : null,
                'receiver_wallet_phone' => $input['receiver_method'] === ReceiverMethod::WALLET->value
                    ? ($input['receiver_wallet_phone'] ?? null)
                    : null,
                'notes' => $input['notes'] ?? null,
                'requested_amount' => $mode === TransferCalculationMode::RECEIVER_AMOUNT
                    ? $input['requested_amount']
                    : $calculation['requested_amount'],
                'requested_currency' => $input['requested_currency'],
                'transfer_amount' => $calculation['transfer_amount'],
                'customer_payable_amount' => $calculation['customer_payable_amount'],
                'customer_payable_currency' => $calculation['customer_payable_currency']->value,
                'commission_amount' => $calculation['commission_amount'],
                'commission_currency' => $calculation['commission_currency']->value,
                'paid_amount' => 0,
                'remaining_amount' => $calculation['customer_payable_amount'],
                'payment_status' => PaymentStatus::UNPAID,
                'calculation_mode' => $mode,
                'status' => TransferStatus::PENDING,
                'created_by' => $actor->id,
            ]);
            $transfer->save();

            if ($initialPayment > 0) {
                $this->receivePaymentService->receive(
                    $transfer,
                    $initialPayment,
                    'Initial payment',
                    $actor->id,
                );
            }

            return $transfer->fresh(['creator', 'payments.receiver']);
        });

        TransferCreated::dispatch($transfer);

        return $transfer;
    }

    public function update(Transfer $transfer, array $input): Transfer
    {
        $mode = TransferCalculationMode::from($input['calculation_mode']);
        $calculation = $this->calculate($input, $mode);

        return DB::transaction(function () use ($transfer, $input, $mode, $calculation): Transfer {
            $transfer = Transfer::query()->lockForUpdate()->findOrFail($transfer->id);

            if ($transfer->status !== TransferStatus::PENDING || $transfer->payment_status !== PaymentStatus::UNPAID) {
                throw new DomainException('Only pending, unpaid transfers can be updated.');
            }

            $transfer->forceFill([
                'receiver_name' => $input['receiver_name'],
                'receiver_method' => $input['receiver_method'],
                'receiver_account_number' => $input['receiver_method'] === ReceiverMethod::BANK->value
                    ? ($input['receiver_account_number'] ?? null)
                    : null,
                'receiver_wallet_phone' => $input['receiver_method'] === ReceiverMethod::WALLET->value
                    ? ($input['receiver_wallet_phone'] ?? null)
                    : null,
                'notes' => $input['notes'] ?? null,
                'requested_amount' => $mode === TransferCalculationMode::RECEIVER_AMOUNT
                    ? $input['requested_amount']
                    : $calculation['requested_amount'],
                'requested_currency' => $input['requested_currency'],
                'transfer_amount' => $calculation['transfer_amount'],
                'customer_payable_amount' => $calculation['customer_payable_amount'],
                'customer_payable_currency' => $calculation['customer_payable_currency']->value,
                'commission_amount' => $calculation['commission_amount'],
                'commission_currency' => $calculation['commission_currency']->value,
                'remaining_amount' => $calculation['customer_payable_amount'],
                'payment_status' => PaymentStatus::UNPAID,
                'calculation_mode' => $mode,
            ]);
            $transfer->save();

            return $transfer->fresh(['creator']);
        });
    }

    private function calculate(array $input, TransferCalculationMode $mode): array
    {
        $requestedCurrency = CurrencyType::from($input['requested_currency']);
        $payCurrency = CurrencyType::from($input['customer_payable_currency']);
        $commission = (float) $input['commission_amount'];

        if ($mode === TransferCalculationMode::CUSTOMER_PAYMENT) {
            return $this->calculator->calculateFromCustomerPayment(
                (float) $input['customer_payable_amount'],
                $payCurrency,
                $requestedCurrency,
                $commission,
            );
        }

        return $this->calculator->calculateFromReceiverAmount(
            (float) $input['requested_amount'],
            $requestedCurrency,
            $payCurrency,
            $commission,
        );
    }

    private function newReferenceNumber(): string
    {
        return Str::upper('TRF-'.Str::random(6));
    }
}
