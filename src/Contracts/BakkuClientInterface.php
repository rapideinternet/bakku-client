<?php

namespace RapideSoftware\BakkuClient\Contracts;

interface BakkuClientInterface
{
    public function getBlocks(string $page): array;
    public function getImages(string $page): array;
    public function getSingleImage(string $imageId);
    public function getPageLinks(): array;
}
