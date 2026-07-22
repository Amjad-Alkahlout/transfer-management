<?php

namespace App\Listeners;

use App\Events\TransferCancelled;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTransferCancelledTelegramNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected NotificationService $notificationService,
    ) {
    }

    public function handle(TransferCancelled $event): void
    {
        $this->notificationService
            ->sendTransferCancelled($event->transfer);
    }
}
