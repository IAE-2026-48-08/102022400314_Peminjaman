<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class AuditSoapService
{
    private Client $http;
    private SsoService $sso;
    private string $baseUrl;
    private string $teamId;

    public function __construct(SsoService $sso)
    {
        $this->sso     = $sso;
        $this->baseUrl = config('services.sso.base_url', 'https://iae-sso.virtualfri.id');
        $this->teamId  = config('services.sso.team_id', 'TEAM-05');
        $this->http    = new Client(['base_uri' => $this->baseUrl, 'timeout' => 15]);
    }

    /**
     * Kirim audit log ke SOAP server dosen.
     *
     * @param  string $activityName  Nama aktivitas bisnis (contoh: CREATE_LOAN)
     * @param  array  $logData       Data transaksi yang akan di-log (array → JSON CDATA)
     * @return string|null           ReceiptNumber dari server, atau null jika gagal
     */
    public function sendAudit(string $activityName, array $logData): ?string
    {
        try {
            $token      = $this->sso->getM2mToken();
            $logContent = json_encode($logData, JSON_UNESCAPED_UNICODE);

            // Build SOAP XML Envelope sesuai format dosen
            $soapBody = $this->buildSoapEnvelope($activityName, $logContent);

            $response = $this->http->post('/soap/v1/audit', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'text/xml; charset=UTF-8',
                    'SOAPAction'    => 'SubmitAudit',
                ],
                'body' => $soapBody,
            ]);

            $xmlContent = $response->getBody()->getContents();
            return $this->parseReceiptNumber($xmlContent);

        } catch (\Exception $e) {
            Log::error('[AuditSoap] Gagal kirim audit: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build SOAP XML Envelope sesuai format dosen (iae:AuditRequest).
     */
    private function buildSoapEnvelope(string $activityName, string $logContent): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope
    xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:iae="http://iae.central/audit">
  <soap:Body>
    <iae:AuditRequest>
      <iae:TeamID>{$this->teamId}</iae:TeamID>
      <iae:ActivityName>{$activityName}</iae:ActivityName>
      <iae:LogContent><![CDATA[{$logContent}]]></iae:LogContent>
    </iae:AuditRequest>
  </soap:Body>
</soap:Envelope>
XML;
    }

    /**
     * Parsing ReceiptNumber dari XML response SOAP.
     */
    private function parseReceiptNumber(string $xmlContent): ?string
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            Log::warning('[AuditSoap] Gagal parse XML response');
            return null;
        }

        // Namespace: iae = http://iae.central/audit
        $xml->registerXPathNamespace('iae', 'http://iae.central/audit');
        $results = $xml->xpath('//iae:ReceiptNumber');

        if (!empty($results)) {
            return (string) $results[0];
        }

        // Fallback: cari tanpa namespace
        $body = $xml->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
        if ($body) {
            foreach ($body->children() as $child) {
                foreach ($child->children() as $item) {
                    if (str_contains($item->getName(), 'ReceiptNumber')) {
                        return (string) $item;
                    }
                }
            }
        }

        return null;
    }
}
