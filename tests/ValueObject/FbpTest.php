<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\ValueObject;

use PHPUnit\Framework\TestCase;

final class FbpTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_sane_defaults(): void
    {
        $fbp = new Fbp();
        self::assertSame($fbp->value(), (string) $fbp);
        self::assertMatchesRegularExpression('/fb\.1\.[0-9]{13}\.[0-9]{10}/', $fbp->value());
    }

    /**
     * @test
     */
    public function it_instantiates_from_string(): void
    {
        $fbp = Fbp::fromString('fb.1.1656874832584.1088522659');

        self::assertSame(1, $fbp->getSubdomainIndex());
        self::assertSame(1656874832584, $fbp->getCreationTime());
        self::assertSame(1088522659, $fbp->getRandomNumber());
    }

    /**
     * @test
     */
    public function it_has_immutable_setters(): void
    {
        $fbp = Fbp::fromString('fb.1.1656874832584.1088522659');
        $newFbp = $fbp->withRandomNumber(123123);

        self::assertNotSame($fbp, $newFbp);
        self::assertSame(1088522659, $fbp->getRandomNumber());
        self::assertSame(123123, $newFbp->getRandomNumber());
    }

    /**
     * @test
     * @dataProvider wrongInputs
     */
    public function it_handles_wrong_input(string $input): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Fbp::fromString($input);
    }

    /**
     * @return list<list<string>>
     */
    public function wrongInputs(): array
    {
        return [
            ['wrong input'],
            ['fb.1.1656874832584.1088522659a'],
            ['afb.1.1656874832584.1088522659'],
        ];
    }
}
