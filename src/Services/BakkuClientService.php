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
    ) {
        $this->httpClientService = $httpClientService;
        $this->apiTransformer = $apiTransformer;
        $this->dataService = $dataService;
    }

    /**
     * Build the API URL based on endpoint.
     */
    private function buildApiUrl(string $type, ?string $id = null, ?string $searchQuery = null, ?string $filter = null): string
    {
        $path = $type . ($id ? '/' . $id : '') . ($filter ?? '');
        $baseUrl = "https://api.bakku.cloud/v1/{config('bakkuclient.site_id')}/{$path}";

        return $searchQuery ? "{$baseUrl}/{$searchQuery}" : $baseUrl;
    }

    /**
     * Determine cache TTL based on environment.
     */
    private function getCacheTtl(): int
    {
        return config('bakkuclient.cache_ttl', 3600);
    }

    /**
     * Get a unique cache key.
     */
    private function getCacheKey(string $type, ?string $id = null, ?string $searchQuery = null, ?string $filter = null): string
    {
        $key = "bakku:{$type}:" . ($id ?? 'all');
        if ($searchQuery) {
            $key .= ':search-' . md5($searchQuery);
        }
        if ($filter) {
            $key .= ':filter-' . md5($filter);
        }
        return $key;
    }

    /**
     * Fetch and cache data from the API.
     */
    private function cacheAndFetch(string $cacheKey, \Closure $callback): array
    {
        return Cache::remember($cacheKey, $this->getCacheTtl(), $callback);
    }

    /**
     * Fetch data from the API.
     */
    private function fetchFromApi(string $type, ?string $id = null, ?string $searchQuery = null, ?string $filter = null): array
    {
        $url = $this->buildApiUrl($type, $id, $searchQuery, $filter);
        $response = $this->httpClientService->get($url, [
            'Authorization' => 'Bearer ' . config('bakkuclient.api_token')
        ]);

        if ($response['status_code'] !== 200) {
            Log::warning('Failed to fetch data from API', [
                'url' => $url,
                'status_code' => $response['status_code'],
                'error' => $response['error']
            ]);
            return [];
        }

        return $response['content'];
    }

    /**
     * Get blocks for a specific page.
     */
    public function getBlocks(string $page): array
    {
        return $this->cacheAndFetch($this->getCacheKey('blocks', $page), function () use ($page) {
            $data = $this->fetchFromApi('documents', $page);
            return $this->apiTransformer->transform($data, 'blocks');
        });
    }

    /**
     * Get images for a specific page.
     */
    public function getImages(string $page): array
    {
        return $this->cacheAndFetch($this->getCacheKey('images', $page), function () use ($page) {
            $data = $this->fetchFromApi('documents', $page);
            return $this->apiTransformer->transform($data, 'images');
        });
    }

    /**
     * Get all page links.
     */
    public function getPageLinks(): array
    {
        return $this->cacheAndFetch($this->getCacheKey('page-links'), function () {
            $data = $this->fetchFromApi('documents');
            return $this->dataService->getPageLinks($data);
        });
    }

    /**
     * Get a single image by ID.
     */
    public function getSingleImage(string $imageId): array
    {
        return $this->cacheAndFetch($this->getCacheKey('single-image', $imageId), function () use ($imageId) {
            $data = $this->fetchFromApi('media', $imageId);
            return $this->apiTransformer->transform($data, 'image');
        });
    }

    /**
     * Get all pages that have the search query on them.
     */
    public function getSearchData(string $searchQuery): array
    {
        return $this->cacheAndFetch($this->getCacheKey('search', null, $searchQuery), function () use ($searchQuery) {
            $data = $this->fetchFromApi('search', null, $searchQuery);
            return $this->dataService->getPageLinks($data);
        });
    }

    /**
     * Get filtered result.
     */
    public function getFilteredData(string $filter, string $type = 'documents'): array
    {
        return $this->cacheAndFetch($this->getCacheKey('filtered-data', null, null, $filter), function () use ($filter, $type) {
            $data = $this->fetchFromApi($type, null, null, $filter);
            return $this->dataService->getPageLinks($data);
        });
    }

    public function getSiteContent(string $endpoint, string $type): array
    {
        return $this->cacheAndFetch($this->getCacheKey($type, $endpoint), function () use ($endpoint, $type) {
            $data = $this->fetchFromApi($type, $endpoint);
            return $this->apiTransformer->transform($data, $type);
        });
    }
}
