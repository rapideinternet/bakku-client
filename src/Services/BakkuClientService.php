<?php

namespace RapideSoftware\BakkuClient\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BakkuClientService
{
    private BakkuClientCacheService $cacheService;
    private BakkuClientDataService $dataService;
    private int $ttl;

    public function __construct(BakkuClientCacheService $cacheService, BakkuClientDataService $dataService)
    {
        $this->cacheService = $cacheService;
        $this->dataService = $dataService;
        $this->ttl = $this->getCacheTtl();
    }

    /**
     * Build the API URL based on endpoint
     */
    private function buildApiUrl(string $endpoint): string
    {
        return sprintf('https://api.bakku.cloud/v1/%s/%s', config('bakkuclient.site_id'), $endpoint);
    }

    /**
     * Generate a cache key based on ID and type
     */
    private function getCacheKey(?string $id, string $type): string
    {
        return sprintf('%s:%s', $type, $id ?? 'default');
    }

    /**
     * Determine cache TTL based on environment
     */
    private function getCacheTtl(): int
    {
        return (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.dev.')) ? 1 : 30;
    }

    /**
     * Fetch data from the API and return the response
     */
    private function fetchFromApi(string $endpoint): array
    {
        $url = $this->buildApiUrl($endpoint);

        try {
            $response = Http::withToken(config('bakkuclient.api_token'))
                ->timeout(10)
                ->retry(3, 100)
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
     * Fetch JSON data with optional cache support
     */
    private function fetchSiteContent(?string $id = null, string $type = 'documents'): JsonResponse
    {
        $cacheKey = $this->getCacheKey($id, $type);
        $cachedData = $this->cacheService->get($cacheKey);

        if ($cachedData) {
            $content = json_decode($cachedData, false);
            $source = 'Fetched from cache';
            $statusCode = 200;
        } else {
            $response = $this->fetchFromApi($type . ($id ? '/' . $id : ''));
            $statusCode = $response['status_code'];
            $content = $response['content'];
            $source = $statusCode === 200 ? 'Fetched from API' : 'Error fetching from API';

            if ($statusCode === 200) {
                $this->cacheService->set($cacheKey, json_encode($content), $this->ttl);
            }
        }

        return response()->json([
            'success' => $statusCode === 200,
            'data' => $content,
            'message' => $source,
        ], $statusCode);
    }

    /**
     * Fetch data from API and return the data or empty array on failure
     */
    public function fetchData(string $endpoint, string $type = 'documents')
    {
        $jsonResponse = $this->fetchSiteContent($endpoint, $type);

        if ($jsonResponse->status() !== 200) {
            return [];
        }

        return $jsonResponse->original['data'];
    }

    /**
     * Get blocks for a specific page
     */
    public function getBlocks(string $page): array
    {
        $data = $this->fetchData($page);
        return $data->data->attributes->blocks ?? [];
    }

    /**
     * Get images for a specific page
     */
    public function getImages(string $page): array
    {
        $data = $this->fetchData($page);
        return $data->included ?? [];
    }

    /**
     * Get a single image by ID
     */
    public function getSingleImage(string $imageId)
    {
        $data = $this->fetchData($imageId, 'media');
        return $data->data->attributes;
    }

    /**
     * Get all page links
     */
    public function getPageLinks(): array
    {
        $json = $this->fetchSiteContent();
        return $this->dataService->getPageLinks($json->original['data']);
    }
}
