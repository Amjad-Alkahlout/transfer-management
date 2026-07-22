<?php

namespace App\Http\Controllers;

use App\Services\TelegramLinkService;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{

    public function __invoke(
        Request $request,
        TelegramLinkService $telegramLinkService,
        TelegramNotificationService $telegram,
    ): Response {

        $text = $request->input('message.text');
        Log::info('Telegram Text', [
            'text' => $text,
        ]);

        if (! $text) {
            return response()->noContent();
        }

        $text = trim($text);

        if (! str_starts_with($text, '/link')) {
            return response()->noContent();
        }

        [$command, $code] = array_pad(explode(' ', $text, 2), 2, null);


        if (blank($code)) {

            $telegram->sendMessage(
                (string) $request->input('message.chat.id'),
                '❌ Please send: /link YOUR_CODE'
            );

            return response()->noContent();
        }

        $user = $telegramLinkService->findByLinkCode($code);


        if (! $user) {

            $telegram->sendMessage(
                (string) $request->input('message.chat.id'),
                "❌ Invalid or expired link code."
            );

            return response()->noContent();
        }

        $telegramLinkService->link(
            $user,
            (string) $request->input('message.chat.id'),
        );

        $telegram->sendMessage(
            (string) $request->input('message.chat.id'),
            "✅ Telegram linked successfully."
        );

        return response()->noContent();
    }
}
