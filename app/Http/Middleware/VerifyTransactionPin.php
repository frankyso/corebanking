<?php

namespace App\Http\Middleware;

use App\Models\MobileUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class VerifyTransactionPin
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

        $pin = $request->header('X-Transaction-Pin');

        if (! $pin) {
            return response()->json([
                'message' => 'PIN transaksi diperlukan.',
            ], 422);
        }

        if ($mobileUser->isPinLocked()) {
            return response()->json([
                'message' => 'PIN terkunci. Coba lagi setelah '.$mobileUser->pin_locked_until?->format('H:i').'.',
            ], 423);
        }

        if (! Hash::check($pin, (string) $mobileUser->pin_hash)) {
            $mobileUser->incrementPinAttempts();

            $remaining = 5 - $mobileUser->pin_attempts;

            return response()->json([
                'message' => 'PIN salah. Sisa percobaan: '.$remaining.'.',
                'remaining_attempts' => $remaining,
            ], 403);
        }

        $mobileUser->resetPinAttempts();

        return $next($request);
    }
}
