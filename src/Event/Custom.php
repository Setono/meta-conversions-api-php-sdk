<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

final class Custom implements Parameters
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

    public ?string $numItems = null;

    public ?string $orderId = null;

    public ?float $predictedLtv = null;

    public ?string $searchString = null;

    public ?string $status = null;

    public ?float $value = null;
}
