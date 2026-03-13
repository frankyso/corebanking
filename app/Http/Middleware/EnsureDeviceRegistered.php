<?php

namespace App\Http\Middleware;

use App\Models\MobileUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeviceRegistered
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var MobileUser|null $mobileUser */
        $mobileUser = $request->user('mobile');

        if (! $mobileUser) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $deviceId = $request->header('X-Device-Id');

        if (! $deviceId) {
            return response()->json([
                'message' => 'Device ID diperlukan.',
            ], 422);
        }

        $device = $mobileUser->devices()
            ->where('device_id', $deviceId)
            ->where('is_active', true)
            ->first();

        if (! $device) {
            return response()->json([
                'message' => 'Perangkat tidak terdaftar. Silakan daftarkan perangkat terlebih dahulu.',
                'code' => 'DEVICE_NOT_REGISTERED',
            ], 403);
        }

        $device->update(['last_used_at' => now()]);

        return $next($request);
    }
}
