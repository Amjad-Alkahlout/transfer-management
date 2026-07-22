<?php

namespace App\Listeners;

use App\Events\TransferExecuted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTransferExecutedTelegramNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected NotificationService $notificationService,
    ) {
    }

    public function handle(TransferExecuted $event): void
    {
        $this->notificationService
            ->sendTransferExecuted($event->transfer);
    }
}
