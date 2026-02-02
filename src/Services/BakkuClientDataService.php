<?php

namespace RapideSoftware\BakkuClient\Services;

class BakkuClientDataService
{
    public function getPageLinks($data): array
    {
        if (empty($data->data) || !is_array($data->data)) {
            return [];
        }

        return array_map(fn($response) => [
            'id' => $response->id ?? null,
            'title' => $response->attributes->title ?? null,
            'pageUrl' => $response->attributes->url ?? null,
            'slug' => $response->attributes->slug ?? null,
            'template' => $response->attributes->template_label ?? null,
            'props' => $response->attributes->props ?? null,
            'relatedPageUid' => reset($response->attributes->related_locales) ?? null,
        ], $data->data);
    }
}
