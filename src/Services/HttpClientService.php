<?php

namespace RapideSoftware\BakkuClient\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpClientService
{
    /**
     * Send a GET request to the API.
     */
    public function get(string $url, array $headers = [], int $timeout = 10)
    {
        try {
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->get($url);

            if ($response->failed()) {
                Log::error('API request failed', ['url' => $url, 'status' => $response->status(), 'body' => $response->body()]);
                return ['status_code' => $response->status(), 'content' => null, 'error' => $response->body()];
            }

            return ['status_code' => $response->status(), 'content' => json_decode($response->body(), false)];
        } catch (\Exception $e) {
            Log::error('API request exception', ['exception' => $e->getMessage()]);
            return ['status_code' => 500, 'content' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a POST request to the API.
     */
    public function post(string $url, array $data, array $headers = [])
    {
        try {
            $response = Http::withHeaders($headers)
                ->post($url, $data);

            if ($response->failed()) {
                Log::error('API request failed', ['url' => $url, 'status' => $response->status(), 'body' => $response->body()]);
                return ['status_code' => $response->status(), 'content' => null, 'error' => $response->body()];
            }

            return ['status_code' => $response->status(), 'content' => json_decode($response->body(), false)];
        } catch (\Exception $e) {
            Log::error('API request exception', ['exception' => $e->getMessage()]);
            return ['status_code' => 500, 'content' => null, 'error' => $e->getMessage()];
        }
    }
}
