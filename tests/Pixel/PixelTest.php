<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Pixel;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Setono\MetaConversionsApi\Pixel\Pixel
 */
final class PixelTest extends TestCase
{
    public function it_has_no_access_token_by_default(): void
    {
        $pixel = new Pixel('id');
        self::assertNull($pixel->accessToken);
    }

    public function it_is_stringable(): void
    {
        $pixel = new Pixel('id');
        self::assertSame('id', (string) $pixel);
    }
}
