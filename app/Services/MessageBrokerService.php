<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MessageBrokerService
{
    private Client $http;
    private SsoService $sso;
    private string $baseUrl;

    public function __construct(SsoService $sso)
    {
        $this->sso     = $sso;
        $this->baseUrl = config('services.sso.base_url', 'https://iae-sso.virtualfri.id');
        $this->http    = new Client(['base_uri' => $this->baseUrl, 'timeout' => 10]);
    }

    /**
     * Publish event ke RabbitMQ dosen via iae.central.exchange.
     *
     * @param  string $routingKey  Routing key event (contoh: peminjaman.loan.created)
     * @param  array  $payload     Data event dalam bentuk array
     * @return bool                True jika berhasil, false jika gagal
     */
    public function publish(string $routingKey, array $payload): bool
    {
        try {
            $token = $this->sso->getM2mToken();

            $response = $this->http->post('/api/v1/messages/publish', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'routing_key' => $routingKey,
                    'payload'     => $payload,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $ok   = ($data['status'] ?? '') === 'success';

            if ($ok) {
                Log::info('[MessageBroker] Published: ' . $routingKey);
            }

            return $ok;

        } catch (\Exception $e) {
            Log::error('[MessageBroker] Gagal publish event: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Publish event loan.created ke RabbitMQ.
     */
    public function publishLoanCreated(array $loanData): bool
    {
        return $this->publish('peminjaman.loan.created', array_merge($loanData, [
            'event'     => 'loan_created',
            'service'   => 'Peminjaman-Service',
            'team'      => config('services.sso.team_id', 'TEAM-05'),
            'timestamp' => now()->toIso8601String(),
        ]));
    }
}
