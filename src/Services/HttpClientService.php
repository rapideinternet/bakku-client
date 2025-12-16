<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Services;

use InvalidArgumentException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RapideSoftware\BakkuClient\Exceptions\HttpClientClientException;
use RapideSoftware\BakkuClient\Exceptions\HttpClientNetworkException;
use RapideSoftware\BakkuClient\Exceptions\HttpClientServerException;

class HttpClientService
{
    private string $apiToken;

    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }
    /**
     * Send a GET request to the API.
     * @param string $url
     * @param string[] $headers
     * @param int $timeout
     * @return object
     * @throws HttpClientNetworkException
     * @throws HttpClientClientException
     * @throws HttpClientServerException
     */
    public function get(string $url, array $headers = [], int $timeout = 10): object
    {
        return $this->sendRequest('GET', $url, [], $headers, $timeout);
    }

    /**
     * Send a POST request to the API.
     * @param string $url
     * @param array<mixed> $data
     * @param string[] $headers
     * @param int $timeout
     * @return object
     * @throws HttpClientNetworkException
     * @throws HttpClientClientException
     * @throws HttpClientServerException
     */
    public function post(string $url, array $data, array $headers = [], int $timeout = 10): object
    {
        return $this->sendRequest('POST', $url, $data, $headers, $timeout);
    }

    /**
     * Send an HTTP request to the API.
     * @param string $method
     * @param string $url
     * @param array<mixed> $data
     * @param string[] $headers
     * @param int $timeout
     * @return object
     * @throws HttpClientNetworkException
     * @throws HttpClientClientException
     * @throws HttpClientServerException
     */
    private function sendRequest(string $method, string $url, array $data = [], array $headers = [], int $timeout = 10): object
    {
        try {
            $headers['Authorization'] = 'Bearer ' . $this->apiToken;
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->send($method, $url, $method === 'GET' ? [] : [
                    'json' => $data,
                ]);

            if ($response->failed()) {
                Log::error('API request failed', ['url' => $url, 'method' => $method, 'status' => $response->status(), 'body' => $response->body()]);

                if ($response->clientError()) {
                    throw new HttpClientClientException(
                        "Client error ({$response->status()}) while fetching {$url}: {$response->body()}",
                        $response->status()
                    );
                } elseif ($response->serverError()) {
                    throw new HttpClientServerException(
                        "Server error ({$response->status()}) while fetching {$url}: {$response->body()}",
                        $response->status()
                    );
                }
            }

            $decodedResponse = json_decode($response->body(), false);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new HttpClientServerException("Invalid JSON response from API: {$url}. Error: " . json_last_error_msg());
            }

            return (object)$decodedResponse;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('API connection exception', ['exception' => $e->getMessage(), 'url' => $url, 'method' => $method]);
            throw new HttpClientNetworkException("Network error while connecting to {$url}: {$e->getMessage()}", 0, $e);
        } catch (InvalidArgumentException $e) {
            Log::error('API request general exception', ['exception' => $e->getMessage(), 'url' => $url, 'method' => $method]);
            throw new HttpClientNetworkException("An unexpected error occurred while fetching {$url}: {$e->getMessage()}", 0, $e);
        }
    }
}
