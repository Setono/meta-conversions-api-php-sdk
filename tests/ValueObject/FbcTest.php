<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\ValueObject;

use PHPUnit\Framework\TestCase;

final class FbcTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_sane_defaults(): void
    {
        $fbp = new Fbc('clickid');
        self::assertSame($fbp->value(), (string) $fbp);
        self::assertMatchesRegularExpression('/^fb\.1\.[0-9]{13}\.[a-zA-Z0-9]+$/', $fbp->value());
    }

    /**
     * @test
     */
    public function it_instantiates_from_string(): void
    {
        $fbp = Fbc::fromString('fb.1.1657051589577.IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA');

        self::assertSame(1, $fbp->getSubdomainIndex());
        self::assertSame(1657051589577, $fbp->getCreationTime());
        self::assertSame('IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA', $fbp->getClickId());
    }

    /**
     * @test
     */
    public function it_handles_wrong_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fbc::fromString('wrong input');
    }
}
