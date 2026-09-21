<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Exception;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Assert;
use Setono\MetaConversionsApi\ValueObject\Fbc;

final class InvalidArgumentExceptionTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider catchableAs
     *
     * @param class-string<\Throwable> $class
     */
    public function it_can_be_caught_as_an_sdk_exception_and_as_an_spl_invalid_argument_exception(string $class): void
    {
        try {
            Fbc::fromString('not an fbc value');
            self::fail('Expected an exception');
        } catch (\Throwable $e) {
            self::assertInstanceOf($class, $e);
        }
    }

    /**
     * @return \Generator<string, array{class-string<\Throwable>}>
     */
    public static function catchableAs(): \Generator
    {
        yield 'the SDK interface' => [ExceptionInterface::class];
        yield 'the SDK class' => [InvalidArgumentException::class];
        yield 'the SPL class' => [\InvalidArgumentException::class];
    }

    /**
     * @test
     */
    public function it_is_thrown_by_a_failed_assertion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a string. Got: integer');

        Assert::string(123);
    }
}
