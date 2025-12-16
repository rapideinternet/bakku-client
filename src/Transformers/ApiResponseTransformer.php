<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\Transformers;

use RapideSoftware\BakkuClient\DTO\Block;
use RapideSoftware\BakkuClient\DTO\Image;
use stdClass;

class ApiResponseTransformer
{
    public function transform(object $response, string $type): mixed
    {
        if (!isset($response->data) && $type !== 'single-image') { // Adjust condition for a single image
            return [];
        }

        return match ($type) {
            'blocks' => $this->transformBlocks($response),
            'images' => $this->transformImages($response),
            'single-image' => $this->transformSingleImage($response),
            default => $response->data,
        };
    }

    /**
     * @return Block[]
     */
    private function transformBlocks(object $response): array
    {
        if (empty($response->data->attributes->blocks)) {
            return [];
        }
        return array_map(fn(array $blockData) => Block::fromApiResponse($blockData), $response->data->attributes->blocks);
    }

    /**
     * @return Image[]
     */
    private function transformImages(object $response): array
    {
        if (empty($response->included)) {
            return [];
        }
        return array_map(fn($imageData) => Image::fromApiResponse((object)$imageData), $response->included);
    }

    private function transformSingleImage(object $response): Image
    {
        if (empty($response->data)) {
            return new Image('', '', '');
        }
        // Assuming single image response structure is similar to included array items
        return Image::fromApiResponse($response->data);
    }
}
