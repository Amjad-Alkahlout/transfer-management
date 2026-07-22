<?php

namespace App\Notifications\Telegram;

use App\Models\Transfer;

class TransferCancelledMessage
{
    public static function build(Transfer $transfer): string
    {
        return
            "❌ <b>Transfer Cancelled</b>\n\n".
            "<b>Receiver:</b> {$transfer->receiver_name}\n".
            "<b>Amount:</b> ".number_format($transfer->transfer_amount, 2)." {$transfer->requested_currency->value}\n\n".
            "This transfer has been cancelled.";
    }
}
