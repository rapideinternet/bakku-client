<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Services;

use Illuminate\Support\Facades\Log;
use RapideSoftware\BakkuClient\Contracts\BakkuClientInterface;
use RapideSoftware\BakkuClient\Contracts\CacheInterface;
use RapideSoftware\BakkuClient\Transformers\ApiResponseTransformer;
use RapideSoftware\BakkuClient\Exceptions\BakkuClientApiException;
use RapideSoftware\BakkuClient\Exceptions\HttpClientClientException;
use RapideSoftware\BakkuClient\Exceptions\HttpClientNetworkException;
use RapideSoftware\BakkuClient\Exceptions\HttpClientServerException;
use RapideSoftware\BakkuClient\DTO\Block;
use RapideSoftware\BakkuClient\DTO\Image;
use RapideSoftware\BakkuClient\DTO\PageLink;
use stdClass;

class BakkuClientService implements BakkuClientInterface
{
    // API Types
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
     * @return int
     */
    private function getCacheTtl(): int
    {
        /** @phpstan-ignore-next-line */
        return (int)config('bakkuclient.cache_ttl', 3600);
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
     * Generate cache tags for a given type and ID.
     * @return string[]
     */
    private function generateCacheTags(string $type, ?string $id = null): array
    {
        $tags = ['bakku', $type];
        if ($id) {
            $tags[] = "{$type}:{$id}";
        }
        return $tags;
    }

    /**
     * Fetch and cache data from the API.
     * @param string   $cacheKey
     * @param \Closure $callback
     * @param string[] $tags
     * @return mixed
     */
    private function cacheAndFetch(string $cacheKey, \Closure $callback, array $tags = []): mixed
    {
        if (!empty($tags)) {
            return $this->cacheService->rememberTagged($tags, $cacheKey, (int)$this->getCacheTtl(), $callback);
        }
        return $this->cacheService->remember($cacheKey, (int)$this->getCacheTtl(), $callback);
    }

    /**
     * Fetch data from the API.
     * @return object
     * @throws BakkuClientApiException
     */
    private function fetchFromApi(string $type, ?string $id = null, ?string $searchQuery = null, ?string $filter = null): object
    {
        $url = $this->buildApiUrl($type, $id, $searchQuery, $filter);

        try {
            $responseContent = $this->httpClientService->get($url);
            return $responseContent;
        } catch (HttpClientNetworkException | HttpClientClientException | HttpClientServerException $e) {
            Log::error('Failed to fetch data from API due to HTTP client error', [
                'url' => $url,
                'exception' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new BakkuClientApiException(
                "Failed to fetch data from API: {$url} - " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get blocks for a specific page.
     * @param string $page
     * @return array<Block>
     * @throws BakkuClientApiException
     */
    public function getBlocks(string $page): array
    {
        $cacheKey = $this->getCacheKey(self::CACHE_KEY_BLOCKS, $page);
        $tags = $this->generateCacheTags(self::CACHE_KEY_BLOCKS, $page);
        /** @var array<Block> $result */
        $result = $this->cacheAndFetch($cacheKey, function () use ($page) {
            $data = $this->fetchFromApi(BakkuClientInterface::API_TYPE_DOCUMENTS, $page);
            return $this->apiTransformer->transform($data, self::CACHE_KEY_BLOCKS);
        }, $tags);
        return $result;
    }

    /**
     * Get images for a specific page.
     * @param string $page
     * @return array<Image>
     * @throws BakkuClientApiException
     */
    public function getImages(string $page): array
    {
        $cacheKey = $this->getCacheKey(self::CACHE_KEY_IMAGES, $page);
        $tags = $this->generateCacheTags(self::CACHE_KEY_IMAGES, $page);
        /** @var array<Image> $result */
        $result = $this->cacheAndFetch($cacheKey, function () use ($page) {
            $data = $this->fetchFromApi(BakkuClientInterface::API_TYPE_DOCUMENTS, $page);
            return $this->apiTransformer->transform($data, self::CACHE_KEY_IMAGES);
        }, $tags);
        return $result;
    }

    /**
     * Get all page links.
     * @return array<PageLink>
     * @throws BakkuClientApiException
     */
    public function getPageLinks(): array
    {
        $cacheKey = $this->getCacheKey(self::CACHE_KEY_PAGE_LINKS);
        $tags = $this->generateCacheTags(self::CACHE_KEY_PAGE_LINKS);
        /** @var array<PageLink> $result */
        $result = $this->cacheAndFetch($cacheKey, function () {
            $data = $this->fetchFromApi(BakkuClientInterface::API_TYPE_DOCUMENTS);
            return $this->dataService->getPageLinks($data);
        }, $tags);
        return $result;
    }

    /**
     * Get a single image by ID.
     * @param string $imageId
     * @return Image|stdClass
     * @throws BakkuClientApiException
     */
    public function getSingleImage(string $imageId): Image|stdClass
    {
        $cacheKey = $this->getCacheKey(self::CACHE_KEY_SINGLE_IMAGE, $imageId);
        $tags = $this->generateCacheTags(self::CACHE_KEY_SINGLE_IMAGE, $imageId);
        /** @var Image|stdClass $result */
        $result = $this->cacheAndFetch($cacheKey, function () use ($imageId) {
            $data = $this->fetchFromApi(BakkuClientInterface::API_TYPE_MEDIA, $imageId);
            return $this->apiTransformer->transform($data, self::CACHE_KEY_SINGLE_IMAGE);
        }, $tags);
        return $result;
    }

    /**
     * Get all pages that have the search query on them.
     * @param string $searchQuery
     * @return array<PageLink>
     * @throws BakkuClientApiException
     */
    public function getSearchData(string $searchQuery): array
    {
        $cacheKey = $this->getCacheKey(self::CACHE_KEY_SEARCH, null, $searchQuery);
        $tags = $this->generateCacheTags(self::CACHE_KEY_SEARCH, md5($searchQuery)); // Tag by search query hash
        /** @var array<PageLink> $result */
        $result = $this->cacheAndFetch($cacheKey, function () use ($searchQuery) {
            $data = $this->fetchFromApi(BakkuClientInterface::API_TYPE_SEARCH, null, $searchQuery);
            return $this->dataService->getPageLinks($data);
        }, $tags);
        return $result;
    }

    /**
     * Get filtered result.
     * @param string $filter
     * @param string $type
     * @return array<PageLink>
     * @throws BakkuClientApiException
     */
    public function getFilteredData(string $filter, string $type = BakkuClientInterface::API_TYPE_DOCUMENTS): array
    {
        $cacheKey = $this->getCacheKey(self::CACHE_KEY_FILTERED_DATA, null, null, $filter);
        $tags = $this->generateCacheTags(self::CACHE_KEY_FILTERED_DATA, md5("{$filter}-{$type}")); // Tag by filter and type hash
        /** @var array<PageLink> $result */
        $result = $this->cacheAndFetch($cacheKey, function () use ($filter, $type) {
            $data = $this->fetchFromApi($type, null, null, $filter);
            return $this->dataService->getPageLinks($data);
        }, $tags);
        return $result;
    }

    /**
     * Get site content for a specific endpoint and type.
     * @param string $endpoint The API endpoint to fetch.
     * @param string $type The type of content to retrieve (e.g., 'blocks', 'images', 'documents').
     * @return array<mixed>|\stdClass
     * @throws BakkuClientApiException
     */
    public function getSiteContent(string $endpoint, string $type): array|\stdClass
    {
        $cacheKey = $this->getCacheKey($type, $endpoint);
        $tags = $this->generateCacheTags($type, $endpoint);
        /** @var array<mixed>|\stdClass $result */
        $result = $this->cacheAndFetch($cacheKey, function () use ($endpoint, $type) {
            $data = $this->fetchFromApi($type, $endpoint);
            return $this->apiTransformer->transform($data, $type);
        }, $tags);
        return $result;
    }
}
