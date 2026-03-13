<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheApiResponse
{
    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next, int $ttl = 300): Response
    {
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        $cacheKey = 'open-api:'.md5($request->fullUrl());

        $etag = '"'.md5($cacheKey.$ttl).'"';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        /** @var array{body: string, status: int}|null $cached */
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return response($cached['body'], $cached['status'])
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Cache' => 'HIT',
                    'ETag' => $etag,
                    'Cache-Control' => "public, max-age={$ttl}",
                ]);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            Cache::put($cacheKey, [
                'body' => $response->getContent(),
                'status' => $response->getStatusCode(),
            ], $ttl);
        }

        $response->headers->set('X-Cache', 'MISS');
        $response->headers->set('ETag', $etag);
        $response->headers->set('Cache-Control', "public, max-age={$ttl}");

        return $response;
    }
}
