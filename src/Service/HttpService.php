<?php declare(strict_types=1);
namespace Budgetcontrol\jobs\Service;

use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class HttpService {

    protected string $baseUrl;
    protected string $apiKey;
    protected array $headers = [];

    public function __construct(string $baseUrl, string $apiKey) {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    public function addHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
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
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'X-API-SECRET' => $this->apiKey
        ], $this->headers);

        if (empty($data)) {
            $body = null;
        } else {
            $body = json_encode($data);
            if ($body === false) {
                throw new \RuntimeException('Failed to encode data to JSON: ' . json_last_error_msg());
            }
        }
        $request = new Request($method, $url, $headers, $body);


        $response = $client->sendAsync($request)->wait();

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
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