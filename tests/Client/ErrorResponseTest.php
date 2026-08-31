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
        $this->expectExceptionMessage('The response from Meta/Facebook was not valid JSON');

        ErrorResponse::fromJson('this is not json');
    }

    /**
     * @test
     *
     * @dataProvider responsesWithAnInvalidFormat
     */
    public function it_throws_when_the_response_does_not_have_the_expected_format(string $json): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Expected a JSON response like');

        ErrorResponse::fromJson($json);
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function responsesWithAnInvalidFormat(): \Generator
    {
        yield 'not an array' => ['100'];
        yield 'missing error key' => ['{"foo":"bar"}'];
        yield 'error is null' => ['{"error":null}'];
        yield 'error is not an array' => ['{"error":"Invalid parameter"}'];

        yield 'missing message' => ['{"error":{"type":"OAuthException","code":100,"fbtrace_id":"trace123"}}'];
        yield 'missing type' => ['{"error":{"message":"Invalid parameter","code":100,"fbtrace_id":"trace123"}}'];
        yield 'missing code' => ['{"error":{"message":"Invalid parameter","type":"OAuthException","fbtrace_id":"trace123"}}'];
        yield 'missing fbtrace_id' => ['{"error":{"message":"Invalid parameter","type":"OAuthException","code":100}}'];

        yield 'message is not a string' => ['{"error":{"message":123,"type":"OAuthException","code":100,"fbtrace_id":"trace123"}}'];
        yield 'type is not a string' => ['{"error":{"message":"Invalid parameter","type":123,"code":100,"fbtrace_id":"trace123"}}'];
        yield 'code is not an integer' => ['{"error":{"message":"Invalid parameter","type":"OAuthException","code":"not-an-int","fbtrace_id":"trace123"}}'];
        yield 'fbtrace_id is not a string' => ['{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"fbtrace_id":123}}'];
        yield 'error_subcode is not an integer' => ['{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_subcode":"not-an-int","fbtrace_id":"trace123"}}'];
        yield 'is_transient is not a boolean' => ['{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"is_transient":"yes","fbtrace_id":"trace123"}}'];
        yield 'error_user_title is not a string' => ['{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_user_title":123,"fbtrace_id":"trace123"}}'];
        yield 'error_user_msg is not a string' => ['{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_user_msg":123,"fbtrace_id":"trace123"}}'];
    }
}
