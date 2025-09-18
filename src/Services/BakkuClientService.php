<?php

namespace RapideSoftware\BakkuClient\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use RapideSoftware\BakkuClient\Contracts\BakkuClientInterface;
use RapideSoftware\BakkuClient\Transformers\ApiResponseTransformer;

class BakkuClientService implements BakkuClientInterface
{
    private HttpClientService $httpClientService;
    private ApiResponseTransformer $apiTransformer;
    private BakkuClientDataService $dataService;

    public function __construct(
        HttpClientService $httpClientService,
        ApiResponseTransformer $apiTransformer,
        BakkuClientDataService $dataService
    ){
        $this->httpClientService = $httpClientService;
        $this->apiTransformer = $apiTransformer;
        $this->dataService = $dataService;
    }

    /**
     * Build the API URL based on endpoint
     */
    private function buildApiUrl(string $endpoint, ?string $searchQuery = null, ?string $filter = null): string
    {
        $baseUrl = sprintf('https://api.bakku.cloud/v1/%s/%s', config('bakkuclient.site_id'), $endpoint . ($filter ?? ''));
        return $searchQuery ? "{$baseUrl}/{$searchQuery}" : $baseUrl;
    }

    /**
     * Determine cache TTL based on environment
     */
    private function getCacheTtl(): int
    {
        return config('bakkuclient.cache_ttl');
    }

    /**
     * Fetch data from the API and return the content
     */
    private function fetchFromApi(?string $id = null, string $type = 'documents', ?string $searchQuery = null, ?string $filter = null)
    {
        $url = $this->buildApiUrl($type . ($id ? '/' . $id : ''), $searchQuery, $filter);
        $response = $this->httpClientService->get($url, [
            'Authorization' => 'Bearer ' . config('bakkuclient.api_token')
        ]);

        if ($response['status_code'] !== 200) {
            Log::warning('Failed to fetch data from API', [
                'endpoint' => $type . ($id ? '/' . $id : ''),
                'searchQuery' => $searchQuery,
                'filter' => $filter,
                'status_code' => $response['status_code'],
                'error' => $response['error']
            ]);
            return [];
        }

        return $response['content'];
    }

    /**
     * Get a unique cache key
     */
    private function getCacheKey(string $type, ?string $id = null, ?string $searchQuery = null, ?string $filter = null): string
    {
        $key = sprintf('bakku:%s:%s', $type, $id ?? 'all');
        if ($searchQuery) {
            $key .= ':search-' . md5($searchQuery);
        }
        if ($filter) {
            $key .= ':filter-' . md5($filter);
        }
        return $key;
    }

    /**
     * Get blocks for a specific page with caching
     */
    public function getBlocks(string $page): array
    {
        return Cache::remember($this->getCacheKey('blocks', $page), $this->getCacheTtl(), function () use ($page) {
            $data = $this->fetchFromApi($page, 'documents');
            return $this->apiTransformer->transform($data, 'blocks');
        });
    }

    /**
     * Get images for a specific page with caching
     */
    public function getImages(string $page): array
    {
        return Cache::remember($this->getCacheKey('images', $page), $this->getCacheTtl(), function () use ($page) {
            $data = $this->fetchFromApi($page, 'documents');
            return $this->apiTransformer->transform($data, 'images');
        });
    }

    /**
     * Get all page links with caching
     */
    public function getPageLinks(): array
    {
        return Cache::remember($this->getCacheKey('page-links'), $this->getCacheTtl(), function () {
            $data = $this->fetchFromApi(null, 'documents');
            return $this->dataService->getPageLinks($data);
        });
    }

    /**
     * Get a single image by ID with caching
     */
    public function getSingleImage(string $imageId): array|\stdClass
    {
        return Cache::remember($this->getCacheKey('single-image', $imageId), $this->getCacheTtl(), function () use ($imageId) {
            $data = $this->fetchFromApi($imageId, 'media');
            return $this->apiTransformer->transform($data, 'image');
        });
    }

    /**
     * Get all pages that have the search query on it with caching
     */
    public function getSearchData(string $searchQuery): array
    {
        return Cache::remember($this->getCacheKey('search', null, $searchQuery), $this->getCacheTtl(), function () use ($searchQuery) {
            $data = $this->fetchFromApi('search', $searchQuery);
            return $this->dataService->getPageLinks($data);
        });
    }

    /**
     * Get filtered result with caching
     */
    public function getFilteredData(string $filter, string $type = 'documents'): array
    {
        return Cache::remember($this->getCacheKey('filtered-data', null, null, $filter), $this->getCacheTtl(), function () use ($filter, $type) {
            $data = $this->fetchFromApi(null, $type, null, $filter);
            return $this->dataService->getPageLinks($data);
        });
    }
}
