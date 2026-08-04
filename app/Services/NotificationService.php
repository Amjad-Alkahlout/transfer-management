<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Transfer;
use App\Models\User;
use App\Notifications\Telegram\TransferCancelledMessage;
use App\Notifications\Telegram\TransferCreatedMessage;
use App\Notifications\Telegram\TransferExecutedMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NotificationService
{
    public function __construct(
        protected TelegramNotificationService $telegram,
        protected TransferCreatedMessage $transferCreatedMessage,
    ) {
    }

    public function sendTransferCreated(Transfer $transfer): void
    {
        $message = $this->transferCreatedMessage->build($transfer);

        $users = User::query()
            ->whereIn('role', [
                UserRole::ADMIN,
                UserRole::EXECUTOR,
                UserRole::TRANSFER_EXECUTOR,
            ])
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_notifications_enabled', true)
            ->get();

        foreach ($users as $user) {

            if (blank($user->telegram_chat_id)) {
                continue;
            }

            $this->telegram->sendMessage(
                $user->telegram_chat_id,
                $message
            );
        }
    }

    public function sendTransferCancelled(Transfer $transfer): void
    {
        $message = TransferCancelledMessage::build($transfer);

        $users = User::query()
            ->whereIn('role', [
                UserRole::ADMIN,
                UserRole::EXECUTOR,
                UserRole::TRANSFER_EXECUTOR,
            ])
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_notifications_enabled', true)
            ->get();

        foreach ($users as $user) {

            $this->telegram->sendMessage(
                $user->telegram_chat_id,
                $message,
            );
        }
    }

    public function sendTransferExecuted(Transfer $transfer): void
    {
        $users = User::query()
            ->whereIn('role', [
                UserRole::ADMIN,
                UserRole::EXECUTOR,
                UserRole::TRANSFER_EXECUTOR,
                UserRole::COORDINATOR,
            ])
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_notifications_enabled', true)
            ->get();

        foreach ($users as $user) {

            $caption = TransferExecutedMessage::build($transfer, $user);

            $this->telegram->sendPhoto(
                $user->telegram_chat_id,
                $transfer->transfer_proof_path,
                $caption,
            );
        }
    }


}
