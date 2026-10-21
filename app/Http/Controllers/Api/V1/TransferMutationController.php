<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\Api\IdempotencyConflictException;
use App\Exceptions\ExchangeRatesUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTransferRequest;
use App\Http\Requests\Api\V1\UpdateTransferRequest;
use App\Http\Resources\Api\V1\TransferResource;
use App\Models\Transfer;
use App\Services\ApiIdempotencyService;
use App\Services\TransferManagementService;
use DomainException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class TransferMutationController extends Controller
{
    public function store(
        StoreTransferRequest $request,
        TransferManagementService $transfers,
        ApiIdempotencyService $idempotency,
    ): JsonResponse|TransferResource
    {
        $input = $request->validated();
        $key = $request->header('Idempotency-Key');
        validator(['idempotency_key' => $key], ['idempotency_key' => ['required', 'string', 'max:255']])->validate();

        try {
            $result = $idempotency->execute(
                $request->user(),
                $key,
                'transfer-create',
                $input,
                function () use ($request, $transfers, $input): array {
                    $transfer = $transfers->create($input, $request->user());

                    return [
                        'status' => Response::HTTP_CREATED,
                        'body' => ['data' => TransferResource::make($transfer)->resolve($request)],
                    ];
                },
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error('INVALID_COMMISSION', $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (IdempotencyConflictException $exception) {
            return $this->error($exception->errorCode, $exception->getMessage(), Response::HTTP_CONFLICT);
        } catch (ExchangeRatesUnavailableException) {
            return $this->error('EXCHANGE_RATES_UNAVAILABLE', 'Transfer calculation is temporarily unavailable because exchange rates are missing.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->json($result['body'], $result['status']);
    }

    public function update(
        UpdateTransferRequest $request,
        Transfer $transfer,
        TransferManagementService $transfers,
    ): JsonResponse|TransferResource {
        try {
            $transfer = $transfers->update($transfer, $request->validated());
        } catch (DomainException $exception) {
            return $this->error('TRANSFER_NOT_EDITABLE', $exception->getMessage(), Response::HTTP_CONFLICT);
        } catch (InvalidArgumentException $exception) {
            return $this->error('INVALID_COMMISSION', $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ExchangeRatesUnavailableException) {
            return $this->error('EXCHANGE_RATES_UNAVAILABLE', 'Transfer calculation is temporarily unavailable because exchange rates are missing.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return TransferResource::make($transfer);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => ['code' => $code],
        ], $status);
    }
}
