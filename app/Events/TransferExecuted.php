<?php

namespace App\Events;


use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Transfer;

class TransferExecuted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Transfer $transfer,
    ) {
    }

}
