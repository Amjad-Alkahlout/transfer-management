<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTransferRequest;
use App\Http\Requests\Api\V1\UpdateTransferRequest;
use App\Http\Resources\Api\V1\TransferResource;
use App\Models\Transfer;
use App\Services\TransferManagementService;
use DomainException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class TransferMutationController extends Controller
{
    public function store(StoreTransferRequest $request, TransferManagementService $transfers): JsonResponse|TransferResource
    {
        try {
            $transfer = $transfers->create($request->validated(), $request->user());
        } catch (InvalidArgumentException $exception) {
            return $this->error('INVALID_COMMISSION', $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return TransferResource::make($transfer)->response()->setStatusCode(Response::HTTP_CREATED);
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
