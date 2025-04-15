<?php

namespace RapideSoftware\BakkuClient\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RapideSoftware\BakkuClient\Contracts\BakkuClientInterface;
use RapideSoftware\BakkuClient\Contracts\CacheInterface;
use RapideSoftware\BakkuClient\Transformers\ApiResponseTransformer;

class BakkuClientService implements BakkuClientInterface
{
    private CacheInterface $cacheService;
    private BakkuClientDataService $dataService;
    private HttpClientService $httpClientService;
    private ApiResponseTransformer $apiTransformer;
    private int $ttl;

    public function __construct(
        CacheInterface $cacheService,
        BakkuClientDataService $dataService,
        HttpClientService $httpClientService,
        ApiResponseTransformer $apiTransformer,
    ){
        $this->cacheService = $cacheService;
        $this->dataService = $dataService;
        $this->httpClientService = $httpClientService;
        $this->apiTransformer = $apiTransformer;
        $this->ttl = $this->getCacheTtl();
    }

    /**
     * Build the API URL based on endpoint
     */
    private function buildApiUrl(string $endpoint, ?string $searchQuery = null, ?string $filter = null): string
    {
        $baseUrl = sprintf('https://api.bakku.cloud/v1/%s/%s', config('bakkuclient.site_id'), $endpoint . $filter);
        return $searchQuery ? "{$baseUrl}/{$searchQuery}" : $baseUrl;
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
    private function fetchFromApi(string $endpoint, ?string $searchQuery = null, ?string $filter = null): array
    {
        $url = $this->buildApiUrl($endpoint, $searchQuery ?? null, $filter ?? null);
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
    private function fetchSiteContent(?string $id = null, string $type = 'documents', ?string $searchQuery = null, ?string $filter = null): JsonResponse
    {
        $cacheKey = $this->getCacheKey($id . ($filter ? '_'.$filter : null), $type);
        $cachedData = $this->cacheService->get($cacheKey);

        if ($cachedData) {
            $content = json_decode($cachedData, false);
            $source = 'Fetched from cache';
            $statusCode = 200;
        } else {
            $response = $this->fetchFromApi($type . ($id ? '/' . $id : ''), $searchQuery, $filter);
            $statusCode = $response['status_code'];
            $content = $response['content'];
            $source = $statusCode === 200 ? 'Fetched from API' : 'Error fetching from API';

            if ($statusCode === 200 && $searchQuery == null) {
                $this->cacheService->set($cacheKey . ($filter ? '_'.$filter : null), json_encode($content), $this->ttl);
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
        return $this->apiTransformer->transform($data, 'blocks');
    }

    /**
     * Get images for a specific page
     */
    public function getImages(string $page): array
    {
        $data = $this->fetchData($page);
        return $this->apiTransformer->transform($data, 'images');
    }

    /**
     * Get a single image by ID
     */
    public function getSingleImage(string $imageId): array|\stdClass
    {
        $data = $this->fetchData($imageId, 'media');
        return $this->apiTransformer->transform($data, 'image');
    }

    /**
     * Get all page links
     */
    public function getPageLinks(): array
    {
        $json = $this->fetchSiteContent();
        return $this->dataService->getPageLinks($json->original['data']);
    }

    /**
     * Get all pages that have the search query on it
     */
    public function getSearchData(string $searchQuery): array
    {
        $json = $this->fetchSiteContent(null, 'search', $searchQuery);
        return $this->dataService->getPageLinks($json->original['data']);
    }

    /**
     * Get filtered result
     */
    public function getFilteredData(string $filter, string $type = 'documents'): array
    {
        $json = $this->fetchSiteContent(null, $type, null, $filter);
        return $this->dataService->getPageLinks($json->original['data']);
    }
}
