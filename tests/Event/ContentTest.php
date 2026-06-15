<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Setono\MetaConversionsApi\Event\Content
 */
final class ContentTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates_payload(): void
    {
        $content = new Content('product_id', 2, 99.95, 'home_delivery');

        self::assertSame([
            'id' => 'product_id',
            'quantity' => 2,
            'item_price' => 99.95,
            'delivery_category' => 'home_delivery',
        ], $content->getPayload());
    }

    /**
     * @test
     */
    public function it_filters_empty_values_from_the_payload(): void
    {
        $content = new Content('product_id');

        self::assertSame(['id' => 'product_id'], $content->getPayload());
    }
}
