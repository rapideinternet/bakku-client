<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Services;

use RapideSoftware\BakkuClient\DTO\PageLink;

class BakkuClientDataService
{
    /**
     * @param object $data
     * @return array<PageLink>
     */
    public function getPageLinks(object $data): array
    {
        if (empty($data->data) || !is_array($data->data)) {
            return [];
        }

        return array_map(fn(object $response) => PageLink::fromApiResponse($response), $data->data);
    }
}
