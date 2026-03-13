<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiClient
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->header('X-Client-Id');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        if (! $clientId || ! $timestamp || ! $signature) {
            return response()->json([
                'message' => 'Missing authentication headers. Required: X-Client-Id, X-Timestamp, X-Signature',
            ], 401);
        }

        $client = ApiClient::where('client_id', $clientId)->first();

        if (! $client) {
            return response()->json([
                'message' => 'Invalid client credentials.',
            ], 401);
        }

        if (! $client->is_active) {
            return response()->json([
                'message' => 'API client is inactive.',
            ], 403);
        }

        $allowedIps = $client->allowed_ips;

        if (is_array($allowedIps) && $allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            return response()->json([
                'message' => 'IP address not allowed.',
            ], 403);
        }

        try {
            $requestTime = Carbon::parse($timestamp);
        } catch (\Exception) {
            return response()->json([
                'message' => 'Invalid timestamp format.',
            ], 401);
        }

        if ($requestTime->diffInMinutes(now(), true) > 5) {
            return response()->json([
                'message' => 'Request timestamp has expired.',
            ], 401);
        }

        $bodyHash = hash('sha256', $request->getContent());
        $stringToSign = implode("\n", [
            $request->method(),
            $request->path(),
            $timestamp,
            $bodyHash,
        ]);

        $expectedSignature = hash_hmac('sha256', $stringToSign, $client->secret_key);

        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'message' => 'Invalid signature.',
            ], 401);
        }

        $request->attributes->set('api_client', $client);

        $client->updateQuietly(['last_used_at' => now()]);

        return $next($request);
    }
}
