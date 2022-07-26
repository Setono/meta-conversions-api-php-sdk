<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

final class Custom extends Parameters
{
    public ?string $contentCategory = null;

    /** @var list<string> */
    public array $contentIds = [];

    public ?string $contentName = null;

    public ?string $contentType = null;

    /** @var list<Content> */
    public array $contents = [];

    public ?string $currency = null;

    public ?string $deliveryCategory = null;

    public ?int $numItems = null;

    public ?string $orderId = null;

    public ?float $predictedLtv = null;

    public ?string $searchString = null;

    public ?string $status = null;

    public ?float $value = null;

    /**
     * This holds an array of custom properties. See https://developers.facebook.com/docs/meta-pixel/implementation/conversion-tracking#custom-properties
     *
     * NOTICE that if you define a custom property with the same name as any of the standard properties, your
     * custom property will be overridden by the value of the standard property
     *
     * @var array<string, mixed>
     */
    public array $customProperties = [];

    protected function getMapping(string $context): array
    {
        return array_merge($this->customProperties, [
            'content_category' => $this->contentCategory,
            'content_ids' => $this->contentIds,
            'content_name' => $this->contentName,
            'content_type' => $this->contentType,
            'contents' => $this->contents,
            'currency' => $this->currency,
            'delivery_category' => $this->deliveryCategory,
            'num_items' => $this->numItems,
            'order_id' => $this->orderId,
            'predicted_ltv' => $this->predictedLtv,
            'search_string' => $this->searchString,
            'status' => $this->status,
            'value' => $this->value,
        ]);
    }

    /**
     * @see \FacebookAds\Object\ServerSide\CustomData::normalize
     */
    protected static function getNormalizedFields(): array
    {
        return [
            'currency', 'delivery_category',
        ];
    }
}
