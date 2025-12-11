<?php

namespace App\Http\Controllers\Api;

use App\Enums\UsageMetricTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Repositories\UsageMetricRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UsageMetricRepository $usageMetricRepository
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = $this->userRepository->create(
            $validated['name'],
            $validated['email'],
            $validated['password']
        );
        $token = $user->createToken('auth-token')->plainTextToken;
        $this->usageMetricRepository->create($user, UsageMetricTypeEnum::REGISTER);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token,
            'type_token' => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = $this->userRepository->findByEmail($validated['email']);
        if (! $user || ! $this->userRepository->checkPassword($user, $validated['password'])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        $user->tokens()->delete();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->usageMetricRepository->create($user, UsageMetricTypeEnum::LOGIN);

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'type_token' => 'Bearer',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        $this->usageMetricRepository->create($request->user(), UsageMetricTypeEnum::LOGOUT);

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    public function user(Request $request): JsonResource
    {
        return new UserResource($request->user());
    }
}
