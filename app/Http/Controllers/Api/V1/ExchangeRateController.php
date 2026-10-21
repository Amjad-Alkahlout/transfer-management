<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\CurrencyType;
use App\Services\ExchangeRateManagementService;
use Illuminate\Validation\Rule;

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

    public function update(Request $request, string $currency, ExchangeRateManagementService $service): JsonResponse
    {
        $request->merge(['currency' => $currency]);
        $data = $request->validate(['currency' => ['required', Rule::enum(CurrencyType::class)], 'rate_to_usd' => ['required', 'numeric', 'decimal:0,8', 'gt:0']]);
        $rate = $service->update(CurrencyType::from($data['currency']), (string) $data['rate_to_usd']);
        return response()->json(['data' => ['currency' => $rate->currency->value, 'rate_to_usd' => number_format((float) $rate->rate_to_usd, 8, '.', ''), 'updated_at' => $rate->updated_at?->toIso8601String()]]);
    }
}
