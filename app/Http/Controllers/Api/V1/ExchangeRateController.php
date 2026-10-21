<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use Illuminate\Http\JsonResponse;

class ExchangeRateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => ExchangeRate::query()
                ->orderBy('currency')
                ->get()
                ->map(fn (ExchangeRate $rate): array => [
                    'currency' => $rate->currency->value,
                    'rate_to_usd' => number_format((float) $rate->rate_to_usd, 8, '.', ''),
                    'updated_at' => $rate->updated_at?->toIso8601String(),
                ]),
        ]);
    }
}
