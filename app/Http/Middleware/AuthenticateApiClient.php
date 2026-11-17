<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a request through only with the bearer token of an active API client.
 */
class AuthenticateApiClient
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (blank($token)) {
            return $this->unauthorized('No token provided.');
        }

        $client = ApiClient::findActiveByToken($token);

        if ($client === null) {
            return $this->unauthorized('Invalid or revoked token.');
        }

        if ($client->last_used_at === null || $client->last_used_at->lt(now()->subMinutes(5))) {
            $client->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        $request->attributes->set('api_client', $client);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return response()->json(['error' => ['code' => 'UNAUTHORIZED', 'message' => $message, 'status' => 401]], 401);
    }
}
