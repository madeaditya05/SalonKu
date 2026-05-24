<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\AppNotificationService;
use App\Support\ProviderMenuAccess;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function registerCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'] ?? null,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'customer',
            ]);

            CustomerProfile::create([
                'user_id' => $user->id,
                'phone_number' => $validated['phone_number'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'status' => 'active',
            ]);

            return $user->load('customerProfile');
        });

        return response()->json([
            'message' => 'Customer registration successful.',
            'user' => $user,
            'token' => $user->createToken('api-token')->plainTextToken,
        ], 201);
    }

    public function registerProvider(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'country_code' => ['required', 'string', 'max:15'],
            'phone_number' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'service_category' => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $phoneNumber = preg_replace('/\s+/', '', $validated['phone_number']);

            $user = User::create([
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'provider',
            ]);

            ProviderProfile::create([
                'user_id' => $user->id,
                'phone_number' => trim($validated['country_code'] . ' ' . $phoneNumber),
                'category' => $validated['service_category'] ?? null,
                'status' => 'inactive',
                'document_status' => 'pending',
            ]);

            return $user->load('providerProfile');
        });

        app(AppNotificationService::class)->createForUsers(
            app(AppNotificationService::class)->adminRecipients(),
            'provider.registered',
            'Provider baru mendaftar',
            ($user->name ?: 'Provider') . ' menunggu review admin.',
            route('admin.providers.show', $user->id),
            [
                'provider_id' => (int) $user->id,
            ],
            (int) $user->id
        );

        return response()->json([
            'message' => 'Provider registration successful. The account is waiting for admin review.',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['nullable', Rule::in(['admin', 'provider', 'customer'])],
        ]);

        $user = User::where('email', $validated['email'])
            ->when($validated['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Email or password is incorrect.',
            ], 401);
        }

        if ($user->role === 'provider') {
            $profile = ProviderProfile::firstOrCreate(
                ['user_id' => ProviderMenuAccess::providerOwnerId($user)],
                ['status' => 'inactive', 'document_status' => 'pending']
            );

            if ($profile->status !== 'active') {
                return response()->json([
                    'message' => 'The provider account is still waiting for admin approval.',
                ], 403);
            }
        }

        if ($user->role === 'customer') {
            $profile = CustomerProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['status' => 'active']
            );

            if ($profile->status !== 'active') {
                return response()->json([
                    'message' => 'The customer account is inactive.',
                ], 403);
            }
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user->load(['customerProfile', 'providerProfile']),
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()?->load(['customerProfile', 'providerProfile']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }
}
