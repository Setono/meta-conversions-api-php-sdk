<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Exception;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Client\ErrorResponse;

/**
 * @covers \Setono\MetaConversionsApi\Exception\ClientException
 */
final class ClientExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_is_created_from_invalid_json(): void
    {
        $jsonException = new \JsonException('Syntax error', \JSON_ERROR_SYNTAX);

        $exception = ClientException::invalidJson($jsonException, 'this is not json');

        self::assertSame(
            'The response from Meta/Facebook was not valid JSON. Given input: this is not json. Error was: Syntax error',
            $exception->getMessage(),
        );
        self::assertSame(0, $exception->getCode());
        self::assertSame($jsonException, $exception->getPrevious());
    }

    /**
     * @test
     */
    public function it_is_created_from_an_invalid_response_format(): void
    {
        $exception = ClientException::invalidResponseFormat('{"foo":"bar"}');

        self::assertSame(
            'Expected a JSON response like {"error":{"message":"string","type":"string","code":int,"fbtrace_id":"string"}}, but got {"foo":"bar"}',
            $exception->getMessage(),
        );
    }

    /**
     * @test
     */
    public function it_is_created_from_an_error_response(): void
    {
        $json = '{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"fbtrace_id":"trace123"}}';

        $exception = ClientException::fromErrorResponse(ErrorResponse::fromJson($json));

        self::assertSame(
            "An error occurred sending an event to Meta/Facebook: Invalid parameter (code: 100, type: OAuthException, trace id: trace123)\n\nRaw JSON response:\n\n" . $json,
            $exception->getMessage(),
        );
    }

    /**
     * @test
     */
    public function it_includes_the_subcode_and_the_user_facing_message_when_present(): void
    {
        $json = '{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_subcode":2804050,"is_transient":false,"error_user_title":"Customer information parameters","error_user_msg":"This event has insufficient customer information.","fbtrace_id":"trace123"}}';

        $exception = ClientException::fromErrorResponse(ErrorResponse::fromJson($json));

        self::assertSame(
            "An error occurred sending an event to Meta/Facebook: Invalid parameter (code: 100, subcode: 2804050, type: OAuthException, trace id: trace123)\n\nCustomer information parameters This event has insufficient customer information.\n\nRaw JSON response:\n\n" . $json,
            $exception->getMessage(),
        );
    }

    /**
     * @test
     *
     * @dataProvider partialUserFacingMessages
     */
    public function it_includes_the_user_facing_message_when_only_the_title_or_the_message_is_present(string $json, string $expectedUserFacingMessage): void
    {
        $exception = ClientException::fromErrorResponse(ErrorResponse::fromJson($json));

        self::assertSame(
            "An error occurred sending an event to Meta/Facebook: Invalid parameter (code: 100, type: OAuthException, trace id: trace123)\n\n" . $expectedUserFacingMessage . "\n\nRaw JSON response:\n\n" . $json,
            $exception->getMessage(),
        );
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function partialUserFacingMessages(): \Generator
    {
        yield 'only the title' => [
            '{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_user_title":"Customer information parameters","fbtrace_id":"trace123"}}',
            'Customer information parameters',
        ];

        yield 'only the message' => [
            '{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_user_msg":"This event has insufficient customer information.","fbtrace_id":"trace123"}}',
            'This event has insufficient customer information.',
        ];
    }
}
