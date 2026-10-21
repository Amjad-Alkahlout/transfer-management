<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Branch;
use App\Enums\CapitalAccountType;
use App\Enums\CurrencyType;
use App\Enums\PaymentStatus;
use App\Enums\TransferStatus;
use App\Http\Controllers\Controller;
use App\Models\CapitalAccount;
use App\Models\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardSummaryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $outstandingByCurrency = Transfer::query()
            ->where('status', '!=', TransferStatus::CANCELLED->value)
            ->select('customer_payable_currency', DB::raw('SUM(remaining_amount) as total'))
            ->groupBy('customer_payable_currency')
            ->orderBy('customer_payable_currency')
            ->get()
            ->map(fn (Transfer $transfer): array => [
                'currency' => $transfer->customer_payable_currency->value,
                'amount' => number_format((float) $transfer->total, 2, '.', ''),
            ])
            ->values();

        return response()->json([
            'data' => [
                'financial_overview' => [
                    'gaza_capital_usd' => $this->balance(Branch::GAZA, CapitalAccountType::CAPITAL, CurrencyType::USD),
                    'uae_capital_usd' => $this->balance(Branch::UAE, CapitalAccountType::CAPITAL, CurrencyType::USD),
                    'uae_capital_aed' => $this->balance(Branch::UAE, CapitalAccountType::CAPITAL, CurrencyType::AED),
                    'gaza_profit_usd' => $this->balance(Branch::GAZA, CapitalAccountType::PROFIT, CurrencyType::USD),
                    'outstanding_receivables' => $outstandingByCurrency,
                ],
                'transfer_counts' => [
                    'pending' => Transfer::where('status', TransferStatus::PENDING)->count(),
                    'completed' => Transfer::where('status', TransferStatus::COMPLETED)->count(),
                    'cancelled' => Transfer::where('status', TransferStatus::CANCELLED)->count(),
                ],
                'payment_counts' => [
                    'unpaid' => Transfer::where('payment_status', PaymentStatus::UNPAID)
                        ->where('status', '!=', TransferStatus::CANCELLED)->count(),
                    'partially_paid' => Transfer::where('payment_status', PaymentStatus::PARTIALLY_PAID)->count(),
                    'paid' => Transfer::where('payment_status', PaymentStatus::PAID)->count(),
                ],
            ],
        ]);
    }

    private function balance(Branch $branch, CapitalAccountType $type, CurrencyType $currency): string
    {
        $balance = CapitalAccount::query()
            ->where('branch', $branch)
            ->where('account_type', $type)
            ->where('currency', $currency)
            ->value('balance');

        return number_format((float) ($balance ?? 0), 2, '.', '');
    }
}
