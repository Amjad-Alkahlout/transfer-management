<?php

namespace App\Notifications\Telegram;

use App\Models\Transfer;

class TransferCreatedMessage
{
    public function build(Transfer $transfer): string
    {
        return
            "🔔 <b>New Transfer Created</b>\n\n".
            "<b>Receiver:</b> {$transfer->receiver_name}\n".
            "<b>Amount:</b> ".number_format($transfer->transfer_amount,2)." {$transfer->requested_currency->value}\n".
            "<b>Method:</b> ".ucfirst($transfer->receiver_method->value)."\n".
            "<b>Created By:</b> {$transfer->creator->name}\n\n".
            "Please review the transfer.";
    }
}
