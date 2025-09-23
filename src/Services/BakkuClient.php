<?php

namespace RapideSoftware\BakkuClient\Services;

class BakkuClient
{
    protected BakkuClientService $bakkuClientService;

    public function __construct(BakkuClientService $bakkuClientService)
    {
        $this->bakkuClientService = $bakkuClientService;
    }

    /**
     * Fetch data from API and return the data or empty array on failure
     */
    public function getSiteContent(string $endpoint, string $type): array
    {
        return $this->bakkuClientService->getSiteContent($endpoint, $type);
    }

    /**
     * Get blocks for a specific page
     */
    public function getBlocks(string $page): array
    {
        return $this->bakkuClientService->getBlocks($page);
    }

    /**
     * Get images for a specific page
     */
    public function getImages(string $page): array
    {
        return $this->bakkuClientService->getImages($page);
    }

    /**
     * Get a single image by ID
     */
    public function getSingleImage(string $imageId): array|\stdClass
    {
        return $this->bakkuClientService->getSingleImage($imageId);
    }

    /**
     * Get all page links
     */
    public function getPageLinks(): array
    {
        return $this->bakkuClientService->getPageLinks();
    }

    /**
     * Get all pages that have the search query on it
     */
    public function getSearchResults(string $searchQuery): array
    {
        return $this->bakkuClientService->getSearchData($searchQuery);
    }

    /**
     * Get data from a filter (e.g. filter[menu]=menu_name)
     */
    public function getFilteredData(string $filter): array
    {
        return $this->bakkuClientService->getFilteredData($filter);
    }
}
