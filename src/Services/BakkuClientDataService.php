<?php

namespace RapideSoftware\BakkuClient\Services;

class BakkuClientDataService
{
    public function getPageLinks($data): array
    {
        $linkArray = [];

        if (isset($data->data) && is_array($data->data)) {
            foreach ($data->data as $response) {
                $linkArray[] = [
                    'id' => $response->id,
                    'pageUrl' => $response->attributes->url ?? null,
                    'slug' => $response->attributes->slug ?? null,
                    'template' => $response->attributes->template_label ?? null,
                ];
            }
        }

        return $linkArray;
    }
}
