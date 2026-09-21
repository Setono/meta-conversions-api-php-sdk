<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApi\ValueObject\Fbp;

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

    /**
     * @test
     */
    public function it_accepts_the_cookies_as_value_objects_and_as_strings(): void
    {
        $user = new User();
        self::assertNull($user->fbc);
        self::assertNull($user->fbp);
        self::assertSame([], $user->getPayload());

        $user->fbc = Fbc::fromString('fb.1.1657051589577.ClickId');
        $user->fbp = Fbp::fromString('fb.1.1656874832584.1088522659');
        self::assertSame(['fbc' => 'fb.1.1657051589577.ClickId', 'fbp' => 'fb.1.1656874832584.1088522659'], $user->getPayload());

        $user->fbc = 'fb.1.1657051589577.OtherClickId';
        $user->fbp = 'fb.1.1656874832584.1234567890';
        self::assertSame(['fbc' => 'fb.1.1657051589577.OtherClickId', 'fbp' => 'fb.1.1656874832584.1234567890'], $user->getPayload());
    }
}
