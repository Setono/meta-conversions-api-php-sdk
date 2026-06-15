<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Exception\ClientException;

/**
 * @covers \Setono\MetaConversionsApi\Client\ErrorResponse
 */
final class ErrorResponseTest extends TestCase
{
    /**
     * @test
     */
    public function it_parses_a_valid_error_response(): void
    {
        $json = '{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"fbtrace_id":"trace123"}}';

        $errorResponse = ErrorResponse::fromJson($json);

        self::assertSame($json, $errorResponse->json);
        self::assertSame('Invalid parameter', $errorResponse->message);
        self::assertSame('OAuthException', $errorResponse->type);
        self::assertSame(100, $errorResponse->code);
        self::assertSame('trace123', $errorResponse->traceId);
    }

    /**
     * @test
     */
    public function it_throws_when_the_response_is_not_valid_json(): void
    {
        $this->expectException(ClientException::class);

        ErrorResponse::fromJson('this is not json');
    }

    /**
     * @test
     */
    public function it_throws_when_the_response_is_not_an_array(): void
    {
        $this->expectException(ClientException::class);

        ErrorResponse::fromJson('100');
    }

    /**
     * @test
     */
    public function it_throws_when_the_error_key_is_missing(): void
    {
        $this->expectException(ClientException::class);

        ErrorResponse::fromJson('{"foo":"bar"}');
    }

    /**
     * @test
     */
    public function it_throws_when_a_field_has_the_wrong_type(): void
    {
        $this->expectException(ClientException::class);

        // the code field must be an int
        ErrorResponse::fromJson('{"error":{"message":"m","type":"t","code":"not-an-int","fbtrace_id":"x"}}');
    }
}
