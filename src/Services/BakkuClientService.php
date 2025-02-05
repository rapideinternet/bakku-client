<?php

namespace RapideSoftware\BakkuClient\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RapideSoftware\BakkuClient\Contracts\BakkuClientInterface;
use RapideSoftware\BakkuClient\Contracts\CacheInterface;

class BakkuClientService implements BakkuClientInterface
{
    private CacheInterface $cacheService;
    private BakkuClientDataService $dataService;
    private HttpClientService $httpClientService;
    private int $ttl;

    public function __construct(
        CacheInterface $cacheService,
        BakkuClientDataService $dataService,
        HttpClientService $httpClientService
    ){
        $this->cacheService = $cacheService;
        $this->dataService = $dataService;
        $this->httpClientService = $httpClientService;
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
        return (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], '.dev.')) ? 1 : config('bakkuclient.cache_ttl', 30);
    }

    /**
     * Fetch data from the API and return the response
     */
    private function fetchFromApi(string $endpoint): array
    {
        $url = $this->buildApiUrl($endpoint);
        $response = $this->httpClientService->get($url, [
            'Authorization' => 'Bearer ' . config('bakkuclient.api_token')
        ]);

        if ($response['status_code'] !== 200) {
            return ['status_code' => $response['status_code'], 'content' => null, 'error' => $response['error']];
        }

        return ['status_code' => $response['status_code'], 'content' => $response['content']];
    }

    /**
     * Fetch JSON data with optional cache support
     */
    private function fetchSiteContent(?string $id = null, string $type = 'documents'): JsonResponse
    {
        $cacheKey = $this->getCacheKey($id, $type);
        $cachedData = $this->cacheService->get($cacheKey);

        if ($cachedData) {
            return response()->json(json_decode($cachedData, false), 200);
        }

        $response = $this->fetchFromApi($type . ($id ? '/' . $id : ''));
        if ($response['status_code'] === 200) {
            $this->cacheService->set($cacheKey, json_encode($response['content']), $this->ttl);
        }

        return response()->json([
            'success' => $response['status_code'] === 200,
            'data' => $response['content'],
            'message' => $response['status_code'] === 200 ? 'Fetched from API' : 'Error fetching from API',
        ], $response['status_code']);
    }

    /**
     * Fetch data from API and return the data or empty array on failure
     */
    public function fetchData(string $endpoint, string $type = 'documents')
    {
        $jsonResponse = $this->fetchSiteContent($endpoint, $type);

        if ($jsonResponse->status() !== 200) {
            Log::warning('Failed to fetch data', ['endpoint' => $endpoint]);
            return [];
        }

        return $jsonResponse->original['data'] ?? [];
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
