<?php declare(strict_types=1);

namespace RapideSoftware\BakkuClient\DTO;

class PageLink
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly ?string $title = null,
        public readonly ?string $pageUrl = null,
        public readonly ?string $slug = null,
        public readonly ?string $template = null,
        public readonly ?bool $hidden = null,
        public readonly ?string $metaTitle = null,
        public readonly ?string $metaDescription = null,
        public readonly ?string $metaImage = null,
        public readonly ?string $relatedPageUid = null,
    ) {}

    public static function fromApiResponse(object $data): self
    {
        return new self(
            $data->id ?? null,
            (property_exists($data, 'attributes') ? $data->attributes->title ?? null : null),
            (property_exists($data, 'attributes') ? $data->attributes->url ?? null : null),
            (property_exists($data, 'attributes') ? $data->attributes->slug ?? null : null),
            (property_exists($data, 'attributes') ? $data->attributes->template_label ?? null : null),
            (property_exists($data, 'attributes') && property_exists($data->attributes, 'props') ? $data->attributes->props->hide ?? null : null),
            (property_exists($data, 'attributes') && property_exists($data->attributes, 'props') ? $data->attributes->props->seo_title ?? null : null),
            (property_exists($data, 'attributes') && property_exists($data->attributes, 'props') ? $data->attributes->props->seo_description ?? null : null),
            (property_exists($data, 'attributes') && property_exists($data->attributes, 'props') ? $data->attributes->props->seo_image ?? null : null),
            (property_exists($data, 'attributes') && is_array($data->attributes->related_locales ?? null) ? reset($data->attributes->related_locales) : null) ?? null
        );
    }
}
