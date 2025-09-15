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
            'hidden' => $response->attributes->props->hide ?? null,
            'metaTitle' => $response->attributes->props->seo_title ?? null,
            'metaDescription' => $response->attributes->props->seo_description ?? null,
            'metaImage' => $response->attributes->props->seo_image ?? null,
            'relatedPageUid' => reset($response->attributes->related_locales) ?? null,
        ], $data->data);
    }
}
