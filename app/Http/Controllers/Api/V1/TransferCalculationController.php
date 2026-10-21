<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CurrencyType;
use App\Enums\TransferCalculationMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TransferCalculationRequest;
use App\Services\TransferCalculatorService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TransferCalculationController extends Controller
{
    public function __invoke(
        TransferCalculationRequest $request,
        TransferCalculatorService $calculator,
    ): JsonResponse {
        $input = $request->validated();
        $mode = TransferCalculationMode::from($input['calculation_mode']);
        $requestedCurrency = CurrencyType::from($input['requested_currency']);
        $payCurrency = CurrencyType::from($input['customer_payable_currency']);

        try {
            $calculation = $mode === TransferCalculationMode::CUSTOMER_PAYMENT
                ? $calculator->calculateFromCustomerPayment(
                    (float) $input['customer_payable_amount'],
                    $payCurrency,
                    $requestedCurrency,
                    (float) $input['commission_amount'],
                )
                : $calculator->calculateFromReceiverAmount(
                    (float) $input['requested_amount'],
                    $requestedCurrency,
                    $payCurrency,
                    (float) $input['commission_amount'],
                );
        } catch (InvalidArgumentException $exception) {
            return $this->calculationError('INVALID_COMMISSION', $exception->getMessage());
        } catch (Throwable) {
            return $this->calculationError(
                'CALCULATION_UNAVAILABLE',
                'The transfer could not be calculated with the available exchange rates.',
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        return response()->json([
            'data' => [
                'calculation_mode' => $mode->value,
                'transfer_amount' => number_format((float) $calculation['transfer_amount'], 2, '.', ''),
                'transfer_currency' => $requestedCurrency->value,
                'customer_payable_amount' => number_format((float) $calculation['customer_payable_amount'], 2, '.', ''),
                'customer_payable_currency' => $calculation['customer_payable_currency']->value,
                'commission_amount' => number_format((float) $calculation['commission_amount'], 2, '.', ''),
                'commission_currency' => $calculation['commission_currency']->value,
            ],
        ]);
    }

    private function calculationError(string $code, string $message, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => [
                'code' => $code,
            ],
        ], $status);
    }
}
