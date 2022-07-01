<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

final class Content extends Parameters
{
    public ?string $id = null;

    public ?int $quantity = null;

    public ?float $itemPrice = null;

    public ?string $deliveryCategory = null;

    public function normalize(): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'item_price' => $this->itemPrice,
            'delivery_category' => self::normalizeField('delivery_category', $this->deliveryCategory),
        ];
    }
}
