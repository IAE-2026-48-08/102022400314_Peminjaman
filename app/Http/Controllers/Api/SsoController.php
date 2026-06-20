<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SsoController extends Controller
{
    public function __construct(private SsoService $sso) {}

    /**
     * Proxy login warga ke SSO dosen.
     * Client kirim email+password → service forward ke SSO → return JWT.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $result = $this->sso->loginWarga(
                $request->input('email'),
                $request->input('password')
            );

            if (($result['status'] ?? '') !== 'success') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Login gagal. Email atau password salah.',
                    'errors'  => null,
                ], 401);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Login berhasil',
                'data'    => [
                    'token'      => $result['token'],
                    'token_type' => 'Bearer',
                    'expires_in' => $result['expires_in'] ?? 3600,
                    'profile'    => $result['profile'] ?? null,
                ],
                'meta' => [
                    'service_name' => 'Peminjaman-Service',
                    'sso_provider' => 'iae-sso.virtualfri.id',
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Login gagal. ' . $e->getMessage(),
                'errors'  => null,
            ], 401);
        }
    }
}
