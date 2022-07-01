<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

final class Content implements Parameters
{
    public ?string $id = null;

    public ?int $quantity = null;

    public ?float $itemPrice = null;

    public ?string $deliveryCategory = null;
}
