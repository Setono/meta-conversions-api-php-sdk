<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Setono\MetaConversionsApi\Event\User
 */
final class UserTest extends TestCase
{
    /**
     * @test
     */
    public function it_normalizes(): void
    {
        $user = new User();
        $user->email[] = 'JohnDoe@Example.com';

        self::assertEquals([
            'em' => ['55e79200c1635b37ad31a378c39feb12f120f116625093a19bc32fff15041149'],
        ], $user->getPayload());
    }
}
