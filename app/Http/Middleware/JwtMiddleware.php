<?php

namespace App\Http\Middleware;

use App\Services\SsoService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function __construct(private SsoService $sso) {}

    /**
     * Validasi Bearer JWT token dari SSO dosen.
     * Mendukung dua tipe token:
     * - "user"  : token warga (login manual)
     * - "m2m"   : token service-to-service (API key)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        // Cek header Authorization: Bearer <token>
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Missing Authorization: Bearer header.',
                'errors'  => null,
            ], 401);
        }

        $token = substr($authHeader, 7);

        try {
            // Verify JWT menggunakan public key RSA dari JWKS SSO dosen
            $payload = $this->sso->verifyToken($token);

            // Simpan payload ke request agar bisa diakses di controller
            $request->attributes->set('jwt_payload', $payload);
            $request->attributes->set('jwt_token_type', $payload->token_type ?? 'unknown');

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Token sudah expired, silakan login ulang.',
                'errors'  => null,
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Token tidak valid.',
                'errors'  => null,
            ], 401);
        }

        return $next($request);
    }
}
