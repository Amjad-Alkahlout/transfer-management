<?php

namespace App\Listeners;

use App\Events\TransferCreated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;


class SendTransferCreatedTelegramNotification implements ShouldQueue
{
    use InteractsWithQueue;
    public function __construct(
        protected NotificationService $notificationService,
    ) {
    }

    public function handle(TransferCreated $event): void
    {
        $this->notificationService
            ->sendTransferCreated($event->transfer);
    }
}
