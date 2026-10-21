<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserAdministrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()->orderBy('name')->paginate(min((int) $request->query('per_page', 20), 100));

        return response()->json(['data' => UserResource::collection($users->getCollection())->resolve(), 'meta' => ['current_page' => $users->currentPage(), 'per_page' => $users->perPage(), 'total' => $users->total(), 'last_page' => $users->lastPage()]]);
    }

    public function store(Request $request, UserAdministrationService $service): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:8', 'confirmed'], 'role' => ['required', Rule::enum(UserRole::class)], 'timezone' => ['nullable', 'timezone']]);
        unset($data['password_confirmation']);

        return response()->json(['data' => UserResource::make($service->create($data))->resolve()], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => UserResource::make($user)->resolve()]);
    }

    public function update(Request $request, User $user, UserAdministrationService $service): JsonResponse
    {
        $data = $request->validate(['name' => ['sometimes', 'required', 'string', 'max:255'], 'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)], 'role' => ['sometimes', Rule::enum(UserRole::class)], 'is_active' => ['sometimes', 'boolean'], 'timezone' => ['sometimes', 'nullable', 'timezone']]);

        return response()->json(['data' => UserResource::make($service->update($user, $data, (int) $request->user()->id))->resolve()]);
    }

    public function password(Request $request, User $user, UserAdministrationService $service): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        $service->setPassword($user, $data['password']);

        return response()->json(['data' => ['updated' => true]]);
    }

    public function destroy(Request $request, User $user, UserAdministrationService $service): JsonResponse
    {
        $service->delete($user, (int) $request->user()->id);

        return response()->json(['data' => ['deleted' => true]]);
    }
}
