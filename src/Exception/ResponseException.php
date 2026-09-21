<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Exception;

use Setono\MetaConversionsApi\Client\ErrorResponse;

/**
 * Thrown when Meta/Facebook, or something in between like a proxy, answers with anything but a 200.
 * Whether retrying makes sense depends on the status code and on the error Meta reported
 */
final class ResponseException extends \RuntimeException implements ExceptionInterface
{
    /**
     * @param string $body the raw response body
     * @param ErrorResponse|null $errorResponse the error Meta reported, or null if the body is not in Meta's error format,
     *                                          which typically means that the response came from a proxy or a gateway
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
        public readonly ?ErrorResponse $errorResponse,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(self::message($statusCode, $body, $errorResponse), 0, $previous);
    }

    private static function message(int $statusCode, string $body, ?ErrorResponse $errorResponse): string
    {
        if (null === $errorResponse) {
            return sprintf(
                "Meta/Facebook responded with status code %d and a body that is not in the expected error format:\n\n%s",
                $statusCode,
                $body,
            );
        }

        $message = sprintf(
            'An error occurred sending an event to Meta/Facebook: %s (status code: %d, code: %d',
            $errorResponse->message,
            $statusCode,
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

        return $message . sprintf("\n\nRaw response:\n\n%s", $body);
    }
}
