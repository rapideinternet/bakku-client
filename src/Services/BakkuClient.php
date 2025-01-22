<?php

namespace RapideSoftware\BakkuClient\Services;

use RapideSoftware\BakkuClient\Services\BakkuClientService;

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
    private function fetchData(string $endpoint, string $type)
    {
        return $this->bakkuClientService->fetchData($endpoint, $type);
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
        return $this->bakkuClientService->getBlocks($page);
    }

    /**
     * Get a single image by ID
     */
    public function getSingleImage(string $imageId)
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
}
