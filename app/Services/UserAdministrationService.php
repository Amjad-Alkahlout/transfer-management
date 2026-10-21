<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAdministrationService
{
    public function create(array $data): User
    {
        return User::create([...$data, 'is_active' => true]);
    }

    public function update(User $user, array $data, int $actorId): User
    {
        return DB::transaction(function () use ($user, $data, $actorId): User {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($locked->id === $actorId && array_key_exists('is_active', $data) && ! filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN)) {
                throw ValidationException::withMessages(['user' => 'You cannot deactivate your own account.']);
            }
            $this->guardLastAdmin($locked, $data['role'] ?? $locked->role->value, $data['is_active'] ?? $locked->is_active);
            $locked->fill($data)->save();

            return $locked->refresh();
        });
    }

    public function setPassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);
        $user->tokens()->delete();
    }

    public function deactivate(User $user, int $actorId): User
    {
        $this->guardSelf($user, $actorId);

        return DB::transaction(function () use ($user): User {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->guardLastAdmin($locked, $locked->role->value, false);
            $locked->update(['is_active' => false]);
            $locked->tokens()->delete();

            return $locked->refresh();
        });
    }

    public function delete(User $user, int $actorId): void
    {
        $this->guardSelf($user, $actorId);
        $this->guardLastAdmin($user, $user->role->value, false);
        try {
            $user->delete();
        } catch (QueryException $exception) {
            throw ValidationException::withMessages(['user' => 'This user has related history and cannot be deleted.']);
        }
    }

    private function guardSelf(User $user, int $actorId): void
    {
        if ($user->id === $actorId) {
            throw ValidationException::withMessages(['user' => 'You cannot modify your own account.']);
        }
    }

    private function guardLastAdmin(User $user, string $newRole, bool|string $newActive): void
    {
        $willRemainActiveAdmin = $newRole === UserRole::ADMIN->value && filter_var($newActive, FILTER_VALIDATE_BOOLEAN);
        if ($user->role === UserRole::ADMIN && $user->is_active && ! $willRemainActiveAdmin
            && User::query()->where('role', UserRole::ADMIN)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages(['user' => 'At least one active administrator must remain.']);
        }
    }
}
