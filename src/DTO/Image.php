<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\DTO;

class Image
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $url,
        public readonly ?string $alt = null,
        // Add other common properties if known
    ) {}

    public static function fromApiResponse(object $data): self
    {
        return new self(
            (string)($data->id ?? ''),
            (string)($data->type ?? ''),
            property_exists($data, 'attributes') ? (string)($data->attributes->url ?? '') : '',
            property_exists($data, 'attributes') ? (string)($data->attributes->alt ?? null) : null
        );
    }
}
