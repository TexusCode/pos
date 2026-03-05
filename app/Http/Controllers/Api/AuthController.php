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

        $phone = trim((string) $payload['phone']);
        $password = (string) $payload['password'];

        $user = $this->resolveUserByPhone($phone);

        if (!$user || !$this->matchesPassword($user, $password)) {
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

    private function matchesPassword(User $user, string $password): bool
    {
        $trimmed = trim($password);
        $stored = (string) $user->password;
        $matchesHash = Hash::check($password, (string) $user->password)
            || Hash::check($trimmed, (string) $user->password);

        if ($matchesHash) {
            return true;
        }

        $md5Password = md5($password);
        $md5Trimmed = md5($trimmed);
        $sha1Password = sha1($password);
        $sha1Trimmed = sha1($trimmed);

        $isMd5 = preg_match('/^[a-f0-9]{32}$/i', $stored) === 1;
        $isSha1 = preg_match('/^[a-f0-9]{40}$/i', $stored) === 1;

        if (($isMd5 && (hash_equals(strtolower($stored), strtolower($md5Password)) || hash_equals(strtolower($stored), strtolower($md5Trimmed))))
            || ($isSha1 && (hash_equals(strtolower($stored), strtolower($sha1Password)) || hash_equals(strtolower($stored), strtolower($sha1Trimmed))))) {
            $user->password = $trimmed;
            $user->save();

            return true;
        }

        // Legacy fallback for old plain passwords; once matched we upgrade to hashed.
        if ($password === $user->password || $trimmed === $user->password) {
            $user->password = $trimmed;
            $user->save();

            return true;
        }

        return false;
    }

    private function resolveUserByPhone(string $rawPhone): ?User
    {
        $normalized = $this->normalizePhone($rawPhone);
        $tail = mb_substr($normalized, -9);

        $exact = User::query()
            ->where('phone', $rawPhone)
            ->orWhere('phone', $normalized)
            ->orWhere('phone', '+' . $normalized)
            ->first();

        if ($exact) {
            return $exact;
        }

        $candidates = User::query()
            ->where('phone', 'like', '%' . $tail)
            ->limit(30)
            ->get();

        foreach ($candidates as $candidate) {
            $candidateNormalized = $this->normalizePhone((string) $candidate->phone);
            if ($candidateNormalized === $normalized || mb_substr($candidateNormalized, -9) === $tail) {
                return $candidate;
            }
        }

        return null;
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
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
