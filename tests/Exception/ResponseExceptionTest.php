<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Exception;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Client\ErrorResponse;

final class ResponseExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_describes_the_error_meta_reported(): void
    {
        $json = '{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"fbtrace_id":"trace123"}}';

        $exception = new ResponseException(400, $json, ErrorResponse::fromJson($json));

        self::assertSame(400, $exception->statusCode);
        self::assertSame($json, $exception->body);
        self::assertNotNull($exception->errorResponse);
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
        self::assertSame(
            "An error occurred sending an event to Meta/Facebook: Invalid parameter (status code: 400, code: 100, type: OAuthException, trace id: trace123)\n\nRaw response:\n\n" . $json,
            $exception->getMessage(),
        );
    }

    /**
     * @test
     */
    public function it_includes_the_subcode_and_the_user_facing_message_when_present(): void
    {
        $json = '{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_subcode":2804050,"is_transient":false,"error_user_title":"Customer information parameters","error_user_msg":"This event has insufficient customer information.","fbtrace_id":"trace123"}}';

        $exception = new ResponseException(400, $json, ErrorResponse::fromJson($json));

        self::assertSame(
            "An error occurred sending an event to Meta/Facebook: Invalid parameter (status code: 400, code: 100, subcode: 2804050, type: OAuthException, trace id: trace123)\n\nCustomer information parameters This event has insufficient customer information.\n\nRaw response:\n\n" . $json,
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
        $exception = new ResponseException(400, $json, ErrorResponse::fromJson($json));

        self::assertSame(
            "An error occurred sending an event to Meta/Facebook: Invalid parameter (status code: 400, code: 100, type: OAuthException, trace id: trace123)\n\n" . $expectedUserFacingMessage . "\n\nRaw response:\n\n" . $json,
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

    /**
     * @test
     */
    public function it_describes_a_response_that_is_not_in_metas_error_format(): void
    {
        $previous = new InvalidArgumentException('not json');

        $exception = new ResponseException(502, '<html>Bad Gateway</html>', null, $previous);

        self::assertSame(502, $exception->statusCode);
        self::assertSame('<html>Bad Gateway</html>', $exception->body);
        self::assertNull($exception->errorResponse);
        self::assertSame($previous, $exception->getPrevious());
        self::assertSame(
            "Meta/Facebook responded with status code 502 and a body that is not in the expected error format:\n\n<html>Bad Gateway</html>",
            $exception->getMessage(),
        );
    }
}
