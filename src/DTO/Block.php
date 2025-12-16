<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\DTO;

class Block
{
    /**
     * @param array<string, mixed> $content
     */
    public function __construct(
        public readonly string $type,
        public readonly array $content, // Assuming content is an array
        // Add other common properties if known
    ) {}

    /**
     * @param array{type?: string, content?: array<mixed>} $data
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            (string)($data['type'] ?? ''),
            (array)($data['content'] ?? [])
        );
    }
}
