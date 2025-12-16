<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<RapideSoftware\BakkuClient\DTO\Block> getBlocks(string $page)
 * @method static array<RapideSoftware\BakkuClient\DTO\Image> getImages(string $page)
 * @method static RapideSoftware\BakkuClient\DTO\Image|\stdClass getSingleImage(string $imageId)
 * @method static array<RapideSoftware\BakkuClient\DTO\PageLink> getPageLinks()
 * @method static array<RapideSoftware\BakkuClient\DTO\PageLink> getSearchData(string $searchQuery)
 * @method static array<RapideSoftware\BakkuClient\DTO\PageLink> getFilteredData(string $filter, string $type = 'documents')
 * @method static array<mixed>|object getSiteContent(string $endpoint, string $type)
 * @see \RapideSoftware\BakkuClient\Services\BakkuClientService
 */
class BakkuClient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RapideSoftware\BakkuClient\Contracts\BakkuClientInterface::class;
    }
}
