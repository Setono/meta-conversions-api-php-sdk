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
}
