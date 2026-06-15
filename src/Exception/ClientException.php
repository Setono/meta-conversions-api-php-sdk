<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Exception;

use JsonException;
use Setono\MetaConversionsApi\Client\ErrorResponse;

class ClientException extends \RuntimeException
{
    public static function invalidJson(JsonException $jsonException, string $json): self
    {
        $message = sprintf(
            'The response from Meta/Facebook was not valid JSON. Given input: %s. Error was: %s',
            $json,
            $jsonException->getMessage(),
        );

        return new self($message, 0, $jsonException);
    }

    public static function invalidResponseFormat(string $json): self
    {
        return new self(sprintf(
            'Expected a JSON response like %s, but got %s',
            '{"error":{"message":"string","type":"string","code":int,"fbtrace_id":"string"}}',
            $json,
        ));
    }

    public static function fromErrorResponse(ErrorResponse $errorResponse): self
    {
        $message = sprintf(
            'An error occurred sending an event to Meta/Facebook: %s (code: %d',
            $errorResponse->message,
            $errorResponse->code,
        );

        if (null !== $errorResponse->subcode) {
            $message .= sprintf(', subcode: %d', $errorResponse->subcode);
        }

        $message .= sprintf(', type: %s, trace id: %s)', $errorResponse->type, $errorResponse->traceId);

        // The user_* fields, when present, hold a human readable explanation aimed at the end user
        $userMessage = trim(sprintf('%s %s', $errorResponse->userTitle ?? '', $errorResponse->userMessage ?? ''));
        if ('' !== $userMessage) {
            $message .= sprintf("\n\n%s", $userMessage);
        }

        $message .= sprintf("\n\nRaw JSON response:\n\n%s", $errorResponse->json);

        return new self($message);
    }
}
