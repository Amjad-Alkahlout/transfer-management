<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class TelegramLinkService
{
    public function generateLinkCode(User $user): string
    {
        if (
            $user->telegram_link_code &&
            $user->telegram_link_expires_at &&
            $user->telegram_link_expires_at->isFuture()
        ) {
            return $user->telegram_link_code;
        }

        do {
            $code = Str::upper(Str::random(6));
        } while (
            User::where('telegram_link_code', $code)->exists()
        );

        $user->update([
            'telegram_link_code' => $code,
            'telegram_link_expires_at' => now()->addMinutes(10),
        ]);

        return $code;
    }

    public function findByLinkCode(string $code): ?User
    {
        return User::query()
            ->where('telegram_link_code', $code)
            ->where('telegram_link_expires_at', '>', now())
            ->first();
    }

    public function unlink(User $user): void
    {
        $user->update([
            'telegram_chat_id' => null,
            'telegram_notifications_enabled' => false,
            'telegram_link_code' => null,
            'telegram_link_expires_at' => null,
        ]);
    }

    public function link(
        User $user,
        string $chatId,
    ): void {
        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_notifications_enabled' => true,
            'telegram_link_code' => null,
            'telegram_link_expires_at' => null,
        ]);
    }
}
