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
        $fbc = new Fbc('clickid');
        self::assertSame($fbc->value(), (string) $fbc);
        self::assertMatchesRegularExpression('/^fb\.1\.[0-9]{13}\.[a-zA-Z0-9]+$/', $fbc->value());
    }

    /**
     * @test
     */
    public function it_instantiates_from_string(): void
    {
        $str = 'fb.1.1657051589577.IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA';
        $fbc = Fbc::fromString($str);

        $expectedDateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2022-07-05 20:06:29');

        self::assertNotFalse($expectedDateTime);
        self::assertSame(1, $fbc->getSubdomainIndex());
        self::assertSame(1657051589577, $fbc->getCreationTime());
        self::assertSame(1657051589, $fbc->getCreationTimeAsSeconds());
        self::assertSame($expectedDateTime->getTimestamp(), $fbc->getCreationTimeAsDateTime()->getTimestamp());
        self::assertSame('IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA', $fbc->getClickId());
        self::assertSame($str, $fbc->value());
    }

    /**
     * @test
     */
    public function it_has_immutable_setters(): void
    {
        $fbc = Fbc::fromString('fb.1.1657051589577.ClickId');
        $newFbc = $fbc->withClickId('NewClickId');

        self::assertNotSame($fbc, $newFbc);
        self::assertSame('ClickId', $fbc->getClickId());
        self::assertSame('NewClickId', $newFbc->getClickId());
    }

    /**
     * @test
     */
    public function it_has_immutable_creation_time_setter(): void
    {
        $fbc = Fbc::fromString('fb.1.1657051589577.ClickId');
        $newFbc = $fbc->withCreationTime(1656874832584);

        self::assertNotSame($fbc, $newFbc);
        self::assertSame(1657051589577, $fbc->getCreationTime());
        self::assertSame(1656874832584, $newFbc->getCreationTime());
    }

    /**
     * @test
     *
     * @dataProvider wrongInputs
     */
    public function it_handles_wrong_input(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fbc::fromString($input);
    }

    /**
     * @return \Generator<array-key, array{string}>
     */
    public static function wrongInputs(): \Generator
    {
        yield ['wrong input'];
        yield ['afb.1.1657051589577.IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA'];
        yield 'click id with an illegal character' => ['fb.1.1657051589577.IwAR0rmfgHgx!'];
        yield 'empty click id' => ['fb.1.1657051589577.'];
        yield 'appendix too short' => ['fb.1.1657051589577.IwAR0rmfgHgx.A'];
        yield 'appendix too long' => ['fb.1.1657051589577.IwAR0rmfgHgx.AQECAQMBX'];
        yield 'six segments' => ['fb.1.1657051589577.IwAR0rmfgHgx.AQ.AQ'];
    }

    /**
     * Real click ids are base64url, so they contain - and _
     *
     * @test
     */
    public function it_parses_a_base64url_click_id(): void
    {
        $str = 'fb.1.1657051589577.IwZXh0bgNhZW0CMTAAAR-uK_5w';
        $fbc = Fbc::fromString($str);

        self::assertSame('IwZXh0bgNhZW0CMTAAAR-uK_5w', $fbc->getClickId());
        self::assertSame($str, $fbc->value());
    }

    /**
     * Meta's own parameter builder writes a trailing appendix segment, and so does the browser pixel. We do not
     * interpret it, but a value read from a cookie has to be written back unchanged
     *
     * @test
     *
     * @dataProvider valuesWithAnAppendix
     */
    public function it_round_trips_an_appendix(string $str, string $appendix): void
    {
        $fbc = Fbc::fromString($str);

        self::assertSame($appendix, $fbc->getAppendix());
        self::assertSame('IwAR1a-b_c', $fbc->getClickId());
        self::assertSame($str, $fbc->value());
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function valuesWithAnAppendix(): \Generator
    {
        yield 'two characters' => ['fb.1.1788781160733.IwAR1a-b_c.AQ', 'AQ'];
        yield 'eight characters' => ['fb.1.1788781160733.IwAR1a-b_c.AQECAQMB', 'AQECAQMB'];
    }

    /**
     * @test
     */
    public function it_keeps_the_appendix_through_the_immutable_setters(): void
    {
        $fbc = Fbc::fromString('fb.1.1657051589577.IwAR1a-b_c.AQECAQMB');

        self::assertSame('AQECAQMB', $fbc->withClickId('Other')->getAppendix());
        self::assertSame('fb.2.1657051589577.IwAR1a-b_c.AQECAQMB', $fbc->withSubdomainIndex(2)->value());
    }
}
