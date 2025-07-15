<?php declare(strict_types=1);
namespace Budgetcontrol\jobs\Service;

use Illuminate\Support\Facades\Log;

class HttpService {

    protected string $baseUrl;
    protected string $apiKey;

    public function __construct(string $baseUrl, string $apiKey) {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * Invokes an HTTP request with the specified method, endpoint, and optional data.
     *
     * @param string $method The HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $endpoint The API endpoint or URL path to call
     * @param array $data Optional data to send with the request
     * @return void
     * @throws \Exception May throw exceptions if the HTTP request fails
     */
    protected function invoke(string $method, string $endpoint, array $data = []): void
    {
        $url = $this->baseUrl . $endpoint;
        $client = new \GuzzleHttp\Client();
        $response = $client->request($method, $url, [
            'headers' => [
                'X-API-SECRET' => $this->apiKey
            ],
            'json' => $data
        ]);

        if ($response->getStatusCode() >= 200 ) {
            throw new \RuntimeException('HTTP request failed with status ' . $response->getStatusCode());
        }

        Log::debug('HTTP request successful', [
            'method' => $method,
            'url' => $url,
            'status' => $response->getStatusCode(),
            'response' => json_decode($response->getBody()->getContents(), true)
        ]);
    }


}