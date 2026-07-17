<?php

namespace App\Services;

use App\Enums\CurrencyType;
use App\Models\ProfitAccountTransaction;
use App\Models\Transfer;

class ProfitService
{
    public function addCommission(
        Transfer $transfer,
        float $amount,
    ): void {

        ProfitAccountTransaction::create([
            'transfer_id' => $transfer->id,
            'amount' => $amount,
            'currency' => CurrencyType::USD,
            'description' => 'Transfer commission',
            'created_by' => auth()->id(),
        ]);
    }
}
