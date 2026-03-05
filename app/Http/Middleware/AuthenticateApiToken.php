<?php

namespace App\Http\Middleware;

use App\Models\ApiAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $plainToken = trim($matches[1]);
        $hashedToken = hash('sha256', $plainToken);

        $token = ApiAccessToken::query()
            ->with('user')
            ->where('token_hash', $hashedToken)
            ->first();

        if (!$token || !$token->user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();

            return response()->json([
                'message' => 'Token expired',
            ], 401);
        }

        $token->forceFill([
            'last_used_at' => now(),
        ])->save();

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_access_token', $token);

        return $next($request);
    }
}
