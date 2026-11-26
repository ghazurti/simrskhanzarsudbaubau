<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAppVersion
{
    public function handle(Request $request, Closure $next): Response
    {
        $minVersion = (int) config('attendance.min_app_version', 2);
        if ($minVersion <= 1) {
            return $next($request);
        }

        $clientVersion = (int) $request->header('X-App-Version', 0);
        if ($clientVersion < $minVersion) {
            return response()->json([
                'message' => 'Aplikasi Anda versi lama dan sudah tidak didukung. Silakan perbarui ke APK Absensi RSUD terbaru (v1.1.0).',
                'code' => 'APP_UPDATE_REQUIRED',
                'min_version' => $minVersion,
            ], 426);
        }

        return $next($request);
    }
}
