<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\IdempotencyConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReceivePaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Transfer;
use App\Services\ApiIdempotencyService;
use App\Services\ReceivePaymentService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function store(
        ReceivePaymentRequest $request,
        Transfer $transfer,
        ApiIdempotencyService $idempotency,
        ReceivePaymentService $payments,
    ): JsonResponse {
        $input = $request->validated();
        $key = $input['idempotency_key'];
        unset($input['idempotency_key']);

        try {
            $result = $idempotency->execute(
                $request->user(),
                $key,
                'transfer-payment:'.$transfer->id,
                $input,
                function () use ($request, $transfer, $payments, $input): array {
                    $payment = $payments->receive(
                        $transfer,
                        (float) $input['amount'],
                        $request->user()->id,
                        $input['notes'] ?? null,
                    );

                    return [
                        'status' => Response::HTTP_CREATED,
                        'body' => ['data' => PaymentResource::make($payment)->resolve($request)],
                    ];
                },
            );
        } catch (IdempotencyConflictException $exception) {
            return $this->error($exception->errorCode, $exception->getMessage(), Response::HTTP_CONFLICT);
        } catch (DomainException $exception) {
            return $this->error('PAYMENT_NOT_ALLOWED', $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return response()->json($result['body'], $result['status']);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => ['code' => $code],
        ], $status);
    }
}
