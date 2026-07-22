<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TelegramNotificationService
{

    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://api.telegram.org/bot' . config('services.telegram.token');
    }

    public function sendMessage(string $chatId, string $message): bool
    {

        try {

            $response = Http::post(
                "{$this->baseUrl}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]
            );

            if (! $response->successful()) {

                Log::error('Telegram API Error', [
                    'response' => $response->body(),
                ]);

                return false;
            }

            return true;

        } catch (\Throwable $e) {

            Log::error('Telegram إرسال فشل', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendPhoto(
        string $chatId,
        string $photoPath,
        string $caption,
    ): bool {

        try {

            $response = Http::attach(
                'photo',
                Storage::disk('public')->get($photoPath),
                basename($photoPath),
            )->post(
                "{$this->baseUrl}/sendPhoto",
                [
                    'chat_id' => $chatId,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]
            );

            if (! $response->successful()) {

                Log::error('Telegram Photo API Error', [
                    'response' => $response->body(),
                ]);

                return false;
            }

            return true;

        } catch (\Throwable $e) {

            Log::error('Telegram Photo إرسال فشل', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
