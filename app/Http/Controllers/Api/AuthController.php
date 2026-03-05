<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $payload = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:150'],
            'token_ttl_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
        ]);

        $user = User::query()->where('phone', $payload['phone'])->first();

        if (!$user || !Hash::check($payload['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 422);
        }

        $plainToken = Str::random(80);
        $ttlHours = $payload['token_ttl_hours'] ?? 24 * 30;
        $expiresAt = now()->addHours($ttlHours);

        ApiAccessToken::query()->create([
            'user_id' => $user->id,
            'name' => $payload['device_name'] ?? 'desktop-client',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $plainToken,
            'expires_at' => $expiresAt?->toISOString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $allDevices = (bool) $request->boolean('all_devices', false);
        $user = $request->user();

        if ($allDevices) {
            $user->apiTokens()->delete();
        } else {
            /** @var \App\Models\ApiAccessToken|null $token */
            $token = $request->attributes->get('api_access_token');
            if ($token) {
                $token->delete();
            }
        }

        return response()->json([
            'message' => 'Logged out',
        ]);
    }
}
