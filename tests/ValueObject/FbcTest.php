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
     * @dataProvider wrongInputs
     */
    public function it_handles_wrong_input(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fbc::fromString($input);
    }

    /**
     * @return list<list<string>>
     */
    public function wrongInputs(): array
    {
        return [
            ['wrong input'],
            ['afb.1.1657051589577.IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA'],
            ['fb.1.1657051589577.IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA_'],
        ];
    }
}
