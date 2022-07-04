<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

final class Content extends Parameters
{
    public ?string $id;

    public ?int $quantity;

    public ?float $itemPrice;

    public ?string $deliveryCategory;

    public function __construct(
        string $id = null,
        int $quantity = null,
        float $itemPrice = null,
        string $deliveryCategory = null
    ) {
        $this->id = $id;
        $this->quantity = $quantity;
        $this->itemPrice = $itemPrice;
        $this->deliveryCategory = $deliveryCategory;
    }

    protected function normalize(): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'item_price' => $this->itemPrice,
            'delivery_category' => self::normalizeField('delivery_category', $this->deliveryCategory),
        ];
    }
}
