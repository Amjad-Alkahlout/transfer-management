<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ExecuteTransferRequest;
use App\Http\Requests\Api\V1\ForceCancelTransferRequest;
use App\Http\Resources\Api\V1\TransferResource;
use App\Models\Transfer;
use App\Services\ForceCancelCompletedTransferService;
use App\Services\TransferCancellationService;
use App\Services\TransferExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use DomainException;
use Symfony\Component\HttpFoundation\Response;

class TransferActionController extends Controller
{
    public function execute(
        ExecuteTransferRequest $request,
        Transfer $transfer,
        TransferExecutionService $execution,
    ): JsonResponse|TransferResource {
        $path = $request->file('proof')->store('transfer_proofs', 'public');

        try {
            $transfer = $execution->execute($transfer, $path, $request->user()->id);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);

            if ($exception instanceof DomainException) {
                return $this->error('TRANSFER_NOT_EXECUTABLE', $exception->getMessage(), Response::HTTP_CONFLICT);
            }

            if ($exception instanceof RuntimeException) {
                return $this->error('TRANSFER_EXECUTION_FAILED', 'The transfer could not be executed with the current account state.', Response::HTTP_CONFLICT);
            }

            throw $exception;
        }

        return TransferResource::make($transfer);
    }

    public function cancel(
        \Illuminate\Http\Request $request,
        Transfer $transfer,
        TransferCancellationService $cancellation,
    ): JsonResponse|TransferResource {
        try {
            $transfer = $cancellation->cancel($transfer, $request->user()->id);
        } catch (DomainException $exception) {
            return $this->error('TRANSFER_NOT_CANCELLABLE', $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return TransferResource::make($transfer);
    }

    public function forceCancel(
        ForceCancelTransferRequest $request,
        Transfer $transfer,
        ForceCancelCompletedTransferService $cancellation,
    ): JsonResponse|TransferResource {
        if (! Hash::check($request->validated('password'), $request->user()->password)) {
            return $this->error('INVALID_PASSWORD', __('transfers.errors.invalid_password'), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $cancellation->cancel($transfer, $request->user()->id, $request->validated('notes'));
        } catch (DomainException $exception) {
            return $this->error('TRANSFER_NOT_FORCE_CANCELLABLE', $exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return TransferResource::make($transfer->fresh(['creator', 'payments.receiver']));
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => ['code' => $code],
        ], $status);
    }
}
