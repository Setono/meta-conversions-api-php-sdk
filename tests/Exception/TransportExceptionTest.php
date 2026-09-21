<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

final class TransportExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_wraps_the_exception_from_the_http_client(): void
    {
        $previous = new class('Connection timed out') extends \RuntimeException implements ClientExceptionInterface {
        };

        $exception = new TransportException($previous);

        self::assertSame('The request to Meta/Facebook failed: Connection timed out', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }
}
