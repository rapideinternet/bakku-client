<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Contracts;

use RapideSoftware\BakkuClient\DTO\Block;
use RapideSoftware\BakkuClient\DTO\Image;
use RapideSoftware\BakkuClient\DTO\PageLink;
use stdClass;

interface BakkuClientInterface
{
    public const API_TYPE_DOCUMENTS = 'documents';
    public const API_TYPE_MEDIA = 'media';
    public const API_TYPE_SEARCH = 'search';
    /**
     * Get blocks for a specific page.
     * @param string $page
     * @return array<Block>
     */
    public function getBlocks(string $page): array;

    /**
     * Get images for a specific page.
     * @param string $page
     * @return array<Image>
     */
    public function getImages(string $page): array;

    /**
     * Get a single image by ID.
     * @param string $imageId
     * @return Image|stdClass
     */
    public function getSingleImage(string $imageId): Image|stdClass;

    /**
     * Get all page links.
     * @return array<PageLink>
     */
    public function getPageLinks(): array;

    /**
     * Get all pages that have the search query on them.
     * @param string $searchQuery
     * @return array<PageLink>
     */
    public function getSearchData(string $searchQuery): array;

    /**
     * Get filtered result.
     * @param string $filter
     * @param string $type
     * @return array<PageLink>
     */
    public function getFilteredData(string $filter, string $type = 'documents'): array;

    /**
     * Get site content for a specific endpoint and type.
     * @param string $endpoint The API endpoint to fetch.
     * @param string $type The type of content to retrieve (e.g., 'blocks', 'images', 'documents').
     * @return array<mixed>|object The raw API response content.
     */
    public function getSiteContent(string $endpoint, string $type): array|object;
}
