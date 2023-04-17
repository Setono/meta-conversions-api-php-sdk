<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Pixel;

use PHPUnit\Framework\TestCase;

/**
 * @covers \Setono\MetaConversionsApi\Pixel\Pixel
 */
final class PixelTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_no_access_token_by_default(): void
    {
        $pixel = new Pixel('id');
        self::assertNull($pixel->accessToken);
    }

    /**
     * @test
     */
    public function it_is_stringable(): void
    {
        $pixel = new Pixel('id');
        self::assertSame('id', (string) $pixel);
    }

    /**
     * @test
     */
    public function it_converts_empty_string_access_token_to_null(): void
    {
        $pixel = new Pixel('id', '');
        self::assertNull($pixel->accessToken);
    }
}
