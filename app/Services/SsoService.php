<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SsoService
{
    private Client $http;
    private string $baseUrl;
    private string $apiKey;
    private string $teamId;
    private string $nim;

    public function __construct()
    {
        $this->baseUrl = config('services.sso.base_url', 'https://iae-sso.virtualfri.id');
        $this->apiKey  = config('services.sso.api_key', 'KEY-MHS-325');
        $this->teamId  = config('services.sso.team_id', 'TEAM-05');
        $this->nim     = config('services.sso.nim', '102022400314');
        $this->http    = new Client(['base_uri' => $this->baseUrl, 'timeout' => 10]);
    }

    /**
     * Ambil M2M token menggunakan API Key.
     * Di-cache selama 55 menit agar tidak request ulang setiap saat.
     */
    public function getM2mToken(): string
    {
        return Cache::remember('sso_m2m_token', 3300, function () {
            $response = $this->http->post('/api/v1/auth/token', [
                'json' => [
                    'api_key' => $this->apiKey,
                    'nim'     => $this->nim,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['token'];
        });
    }

    /**
     * Login warga (end-user) ke SSO — proxy untuk endpoint /api/auth/login.
     * Mengembalikan array berisi token dan profile warga.
     */
    public function loginWarga(string $email, string $password): array
    {
        $response = $this->http->post('/api/v1/auth/token', [
            'json' => ['email' => $email, 'password' => $password],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Ambil JWKS (public key RSA) dari SSO.
     * Di-cache selama 24 jam — public key jarang berubah.
     */
    public function getJwks(): array
    {
        return Cache::remember('sso_jwks', 86400, function () {
            $response = $this->http->get('/api/v1/auth/jwks');
            return json_decode($response->getBody()->getContents(), true);
        });
    }

    /**
     * Verifikasi JWT token menggunakan public key dari JWKS SSO.
     * Return decoded payload jika valid, throw exception jika tidak valid.
     */
    public function verifyToken(string $token): object
    {
        $jwks    = $this->getJwks();
        $keySet  = JWK::parseKeySet($jwks);

        // Decode & verify JWT menggunakan RSA public key (RS256)
        $decoded = JWT::decode($token, $keySet);

        return $decoded;
    }
}
