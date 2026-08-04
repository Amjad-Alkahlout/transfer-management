<?php

namespace App\Notifications\Telegram;

use App\Models\Transfer;
use App\Models\User;
use App\Support\LocalTime;

class TransferExecutedMessage
{
    public static function build(Transfer $transfer, ?User $forUser = null): string
    {
        return
            "✅ <b>Transfer Executed</b>\n\n".
            "<b>Reference:</b> {$transfer->reference_number}\n".
            "<b>Receiver:</b> {$transfer->receiver_name}\n".
            "<b>Amount:</b> ".number_format($transfer->transfer_amount,2)." {$transfer->requested_currency->value}\n".
            "<b>Method:</b> {$transfer->receiver_method->value}\n".
            "<b>Completed At:</b> ".LocalTime::format($transfer->completed_at, 'd M Y h:i A', $forUser);
    }
}
