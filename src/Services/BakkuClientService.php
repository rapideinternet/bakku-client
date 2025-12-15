<?php

namespace RapideSoftware\BakkuClient\Services;

use Illuminate\Support\Facades\Log;
use RapideSoftware\BakkuClient\Contracts\BakkuClientInterface;
use RapideSoftware\BakkuClient\Contracts\CacheInterface;
use RapideSoftware\BakkuClient\Transformers\ApiResponseTransformer;
use RapideSoftware\BakkuClient\Exceptions\BakkuClientApiException;
use stdClass;

class BakkuClientService implements BakkuClientInterface
{
    // API Types
    private const API_TYPE_DOCUMENTS = 'documents';
    private const API_TYPE_MEDIA = 'media';
    private const API_TYPE_SEARCH = 'search';

    // Cache Keys
    private const CACHE_KEY_BLOCKS = 'blocks';
    private const CACHE_KEY_IMAGES = 'images';
    private const CACHE_KEY_PAGE_LINKS = 'page-links';
    private const CACHE_KEY_SINGLE_IMAGE = 'single-image';
    private const CACHE_KEY_SEARCH = 'search';
    private const CACHE_KEY_FILTERED_DATA = 'filtered-data';
    private HttpClientService $httpClientService;
    private ApiResponseTransformer $apiTransformer;
    private BakkuClientDataService $dataService;
    private CacheInterface $cacheService;

    public function __construct(
        HttpClientService $httpClientService,
        ApiResponseTransformer $apiTransformer,
        BakkuClientDataService $dataService,
        CacheInterface $cacheService
    ) {
        $this->httpClientService = $httpClientService;
        $this->apiTransformer = $apiTransformer;
        $this->dataService = $dataService;
        $this->cacheService = $cacheService;
    }

    /**
     * Build the API URL based on an endpoint.
     */
    private function buildApiUrl(string $type, ?string $id = null, ?string $searchQuery = null, ?string $filter = null): string
    {
        $path = $type . ($id ? '/' . $id : '') . ($filter ?? '');
        $baseUrl = config('bakkuclient.api_base_url') . config('bakkuclient.site_id') . "/{$path}";

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
    private function cacheAndFetch(string $cacheKey, \Closure $callback): array|\stdClass
    {
        return $this->cacheService->remember($cacheKey, $this->getCacheTtl(), $callback);
    }

    /**
     * Fetch data from the API.
     */
    private function fetchFromApi(string $type, ?string $id = null, ?string $searchQuery = null, ?string $filter = null): array|\stdClass
    {
        $url = $this->buildApiUrl($type, $id, $searchQuery, $filter);
        $response = $this->httpClientService->get($url, [
            'Authorization' => 'Bearer ' . config('bakkuclient.api_token')
        ]);

        if ($response['status_code'] !== 200) {
            Log::error('Failed to fetch data from API', [
                'url' => $url,
                'status_code' => $response['status_code'],
                'error' => $response['error']
            ]);
            throw new BakkuClientApiException(
                "Failed to fetch data from API: {$url} (Status: {$response['status_code']}, Error: {$response['error']})",
                $response['status_code']
            );
        }

        return $response['content'];
    }

    /**
     * Get blocks for a specific page.
     */
    public function getBlocks(string $page): array|\stdClass
    {
        return $this->cacheAndFetch($this->getCacheKey(self::CACHE_KEY_BLOCKS, $page), function () use ($page) {
            $data = $this->fetchFromApi(self::API_TYPE_DOCUMENTS, $page);
            return $this->apiTransformer->transform($data, self::CACHE_KEY_BLOCKS);
        });
    }

    /**
     * Get images for a specific page.
     */
    public function getImages(string $page): array|\stdClass
    {
        return $this->cacheAndFetch($this->getCacheKey(self::CACHE_KEY_IMAGES, $page), function () use ($page) {
            $data = $this->fetchFromApi(self::API_TYPE_DOCUMENTS, $page);
            return $this->apiTransformer->transform($data, self::CACHE_KEY_IMAGES);
        });
    }

    /**
     * Get all page links.
     */
    public function getPageLinks(): array|\stdClass
    {
        return $this->cacheAndFetch($this->getCacheKey(self::CACHE_KEY_PAGE_LINKS), function () {
            $data = $this->fetchFromApi(self::API_TYPE_DOCUMENTS);
            return $this->dataService->getPageLinks($data);
        });
    }

    /**
     * Get a single image by ID.
     */
    public function getSingleImage(string $imageId): array|\stdClass
    {
        return $this->cacheAndFetch($this->getCacheKey(self::CACHE_KEY_SINGLE_IMAGE, $imageId), function () use ($imageId) {
            $data = $this->fetchFromApi(self::API_TYPE_MEDIA, $imageId);
            return $this->apiTransformer->transform($data, self::CACHE_KEY_SINGLE_IMAGE);
        });
    }

    /**
     * Get all pages that have the search query on them.
     */
    public function getSearchData(string $searchQuery): array|\stdClass
    {
        return $this->cacheAndFetch($this->getCacheKey(self::CACHE_KEY_SEARCH, null, $searchQuery), function () use ($searchQuery) {
            $data = $this->fetchFromApi(self::API_TYPE_SEARCH, null, $searchQuery);
            return $this->dataService->getPageLinks($data);
        });
    }

    /**
     * Get filtered result.
     */
    public function getFilteredData(string $filter, string $type = self::API_TYPE_DOCUMENTS): array|\stdClass
    {
        return $this->cacheAndFetch($this->getCacheKey(self::CACHE_KEY_FILTERED_DATA, null, null, $filter), function () use ($filter, $type) {
            $data = $this->fetchFromApi($type, null, null, $filter);
            return $this->dataService->getPageLinks($data);
        });
    }

    public function getSiteContent(string $endpoint, string $type): array|\stdClass
    {
        return $this->cacheAndFetch($this->getCacheKey($type, $endpoint), function () use ($endpoint, $type) {
            $data = $this->fetchFromApi($type, $endpoint);
            return $this->apiTransformer->transform($data, $type);
        });
    }
}
