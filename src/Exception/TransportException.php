<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Exception;

use Psr\Http\Client\ClientExceptionInterface;

/**
 * Thrown when the request never resulted in a response, e.g. because of a network error or a timeout.
 * The exception from the HTTP client is the previous exception. Retrying usually makes sense
 */
final class TransportException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(ClientExceptionInterface $previous)
    {
        parent::__construct(sprintf('The request to Meta/Facebook failed: %s', $previous->getMessage()), 0, $previous);
    }
}
