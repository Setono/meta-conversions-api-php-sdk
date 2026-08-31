<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Setono\MetaConversionsApi\Event\Custom
 */
final class CustomTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates_payload(): void
    {
        $user = new Custom();
        $user->currency = 'DKK';
        $user->customProperties['my_custom_property'] = 'test';

        self::assertEquals([
            'currency' => 'dkk',
            'my_custom_property' => 'test',
        ], $user->getPayload());
    }

    /**
     * @test
     */
    public function it_generates_the_full_payload(): void
    {
        $custom = new Custom();
        $custom->contentCategory = 'Shoes';
        $custom->contentIds = ['PROD_1', 'PROD_2'];
        $custom->contentName = 'Sneakers';
        $custom->contentType = 'product';
        $custom->contents[] = new Content('PROD_1', 1, 99.95);
        $custom->currency = 'DKK';
        $custom->deliveryCategory = 'home_delivery';
        $custom->numItems = 2;
        $custom->orderId = 'ORDER_1';
        $custom->predictedLtv = 500.0;
        $custom->searchString = 'sneakers';
        $custom->status = 'completed';
        $custom->value = 199.9;
        $custom->customProperties['my_custom_property'] = 'test';
        $custom->customProperties['value'] = 'overridden by the standard property';

        self::assertEquals([
            'my_custom_property' => 'test',
            'content_category' => 'Shoes',
            'content_ids' => ['PROD_1', 'PROD_2'],
            'content_name' => 'Sneakers',
            'content_type' => 'product',
            'contents' => [
                ['id' => 'PROD_1', 'quantity' => 1, 'item_price' => 99.95],
            ],
            'currency' => 'dkk',
            'delivery_category' => 'home_delivery',
            'num_items' => 2,
            'order_id' => 'ORDER_1',
            'predicted_ltv' => 500.0,
            'search_string' => 'sneakers',
            'status' => 'completed',
            'value' => 199.9,
        ], $custom->getPayload());
    }

    /**
     * @test
     */
    public function it_rejects_values_it_cannot_normalize(): void
    {
        $custom = new Custom();
        $custom->customProperties['unsupported'] = new \stdClass();

        $this->expectException(\InvalidArgumentException::class);

        $custom->getPayload();
    }
}
