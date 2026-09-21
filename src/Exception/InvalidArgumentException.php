<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Exception;

/**
 * Thrown when the SDK is given something it cannot work with: a cookie value in the wrong format, event data Meta
 * does not accept, a pixel without an access token, and so on. The caller has to fix the input; retrying will not help
 */
final class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
