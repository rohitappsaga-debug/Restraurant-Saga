<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->query('role'), fn ($q, $r) => $q->where('role', $r))
            ->when($request->filled('active'), fn ($q) => $q->where('active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('limit', 20), 100));

        return $this->respond(UserResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        return $this->respond(new UserResource($user));
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return $this->respondCreated(new UserResource($user), 'User created');
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $data = array_filter(
            $request->validated(),
            fn ($value, $key) => !($key === 'password' && empty($value)),
            ARRAY_FILTER_USE_BOTH
        );

        $user->update($data);

        return $this->respond(new UserResource($user->fresh()), 'User updated');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            abort(422, 'You cannot deactivate your own account');
        }

        // Deactivate rather than hard-delete: orders reference their creator
        $user->update(['active' => false]);
        $user->tokens()->delete();

        return $this->respond(new UserResource($user->fresh()), 'User deactivated');
    }
}
