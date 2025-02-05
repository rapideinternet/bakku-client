<?php

namespace RapideSoftware\BakkuClient\Transformers;

class ApiResponseTransformer
{
    public function transform($response, string $type): array
    {
        if (!isset($response->data)) {
            return [];
        }

        return match ($type) {
            'blocks' => $this->transformBlocks($response),
            'images' => $this->transformImages($response),
            'image' => $this->transformSingleImage($response),
            default => $response->data,
        };
    }

    private function transformBlocks($response): array
    {
        return $response->data->attributes->blocks ?? [];
    }

    private function transformImages($response): array
    {
        return $response->included ?? [];
    }

    private function transformSingleImage($response): array
    {
        return $response->data->attributes ?? [];
    }
}
