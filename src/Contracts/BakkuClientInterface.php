<?php

namespace RapideSoftware\BakkuClient\Contracts;

interface BakkuClientInterface
{
    public function getBlocks(string $page): array|\stdClass;
    public function getImages(string $page): array|\stdClass;
    public function getSingleImage(string $imageId);
    public function getPageLinks(): array|\stdClass;
    public function getSearchData(string $searchQuery): array|\stdClass;
    public function getFilteredData(string $filter, string $type = 'documents'): array|\stdClass;
    public function getSiteContent(string $endpoint, string $type): array|\stdClass;
}
