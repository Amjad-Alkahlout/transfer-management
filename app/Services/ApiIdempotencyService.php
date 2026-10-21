<?php

namespace App\Services;

use App\Exceptions\Api\IdempotencyConflictException;
use App\Models\ApiIdempotencyKey;
use App\Models\User;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ApiIdempotencyService
{
    /**
     * Execute a financial operation once per user/key and replay its saved response.
     *
     * @param  Closure(): array{status: int, body: array<string, mixed>}  $operation
     * @return array{status: int, body: array<string, mixed>, replayed: bool}
     */
    public function execute(
        User $user,
        string $key,
        string $action,
        array $payload,
        Closure $operation,
    ): array {
        $requestHash = $this->hashPayload($payload);

        try {
            return DB::transaction(function () use ($user, $key, $action, $requestHash, $operation): array {
                $record = ApiIdempotencyKey::query()
                    ->where('user_id', $user->id)
                    ->where('key', $key)
                    ->lockForUpdate()
                    ->first();

                if ($record) {
                    return $this->replayOrReject($record, $action, $requestHash);
                }

                $record = ApiIdempotencyKey::query()->create([
                    'user_id' => $user->id,
                    'key' => $key,
                    'action' => $action,
                    'request_hash' => $requestHash,
                ]);

                $result = $operation();
                $record->update([
                    'response_status' => $result['status'],
                    'response_body' => $result['body'],
                ]);

                return $result + ['replayed' => false];
            });
        } catch (UniqueConstraintViolationException $exception) {
            $record = ApiIdempotencyKey::query()
                ->where('user_id', $user->id)
                ->where('key', $key)
                ->first();

            if (! $record) {
                throw $exception;
            }

            return $this->replayOrReject($record, $action, $requestHash);
        }
    }

    private function replayOrReject(ApiIdempotencyKey $record, string $action, string $requestHash): array
    {
        if ($record->action !== $action || ! hash_equals($record->request_hash, $requestHash)) {
            throw new IdempotencyConflictException(
                'IDEMPOTENCY_KEY_REUSED',
                'This idempotency key was already used for a different request.',
            );
        }

        if ($record->response_status === null || $record->response_body === null) {
            throw new IdempotencyConflictException(
                'IDEMPOTENCY_REQUEST_IN_PROGRESS',
                'A request using this idempotency key is still being processed.',
            );
        }

        return [
            'status' => $record->response_status,
            'body' => $record->response_body,
            'replayed' => true,
        ];
    }

    private function hashPayload(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }
}
