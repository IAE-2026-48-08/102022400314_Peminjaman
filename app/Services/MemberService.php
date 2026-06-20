<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MemberService
{
    /**
     * Get member status from Keanggotaan Service.
     *
     * @param string $memberId
     * @return array|null
     */
    public function getMemberStatus(string $memberId): ?array
    {
        $baseUrl = env('KEANGGOTAAN_SERVICE_URL', 'http://host.docker.internal:8001');
        $apiKey = env('IAE_API_KEY', '102022400314');

        try {
            $response = Http::withHeaders([
                'X-IAE-KEY' => $apiKey,
                'Accept'    => 'application/json',
            ])->timeout(3)->get("{$baseUrl}/api/v1/members/{$memberId}/status");

            if ($response->successful()) {
                return $response->json();
            }
            
            Log::warning("Keanggotaan API returned non-success code: " . $response->status());
        } catch (\Exception $e) {
            Log::error("Failed to connect to Keanggotaan Service at {$baseUrl}: " . $e->getMessage());
        }

        return null;
    }
}
