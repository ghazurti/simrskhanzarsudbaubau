<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyDeviceBinding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user || $user->isAdmin()) {
            return $next($request);
        }

        $deviceId = trim((string) ($request->header('X-Device-Id') ?: $request->input('device_id', '')));

        if (empty($user->device_id)) {
            return response()->json([
                'message' => 'Perangkat Anda telah direset oleh Admin. Silakan login ulang.',
            ], 401);
        }

        if ($deviceId === '' || $user->device_id !== $deviceId) {
            return response()->json([
                'message' => 'Perangkat tidak sesuai dengan HP yang terdaftar. Silakan gunakan HP Anda sendiri atau hubungi Admin untuk reset perangkat.',
            ], 403);
        }

        return $next($request);
    }
}
