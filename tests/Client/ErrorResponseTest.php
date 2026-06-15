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

        // the optional fields are null when not present
        self::assertNull($errorResponse->subcode);
        self::assertNull($errorResponse->transient);
        self::assertNull($errorResponse->userTitle);
        self::assertNull($errorResponse->userMessage);
    }

    /**
     * @test
     */
    public function it_captures_the_optional_fields_when_present(): void
    {
        $json = '{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_subcode":2804050,"is_transient":false,"error_user_title":"Customer information parameters","error_user_msg":"This event has insufficient customer information.","fbtrace_id":"trace123"}}';

        $errorResponse = ErrorResponse::fromJson($json);

        self::assertSame('Invalid parameter', $errorResponse->message);
        self::assertSame(100, $errorResponse->code);
        self::assertSame(2804050, $errorResponse->subcode);
        self::assertFalse($errorResponse->transient);
        self::assertSame('Customer information parameters', $errorResponse->userTitle);
        self::assertSame('This event has insufficient customer information.', $errorResponse->userMessage);
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
