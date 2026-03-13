<?php

namespace App\Http\Middleware;

use App\Enums\CustomerStatus;
use App\Models\MobileUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var MobileUser|null $mobileUser */
        $mobileUser = $request->user('mobile');

        if (! $mobileUser || ! $mobileUser->is_active) {
            return response()->json([
                'message' => 'Akun mobile banking tidak aktif.',
            ], 403);
        }

        $customer = $mobileUser->customer;

        if (! $customer || $customer->status !== CustomerStatus::Active) {
            return response()->json([
                'message' => 'Status nasabah tidak aktif.',
            ], 403);
        }

        return $next($request);
    }
}
