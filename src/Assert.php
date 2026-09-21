<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi;

use Setono\MetaConversionsApi\Exception\InvalidArgumentException;

/**
 * Makes a failed assertion throw the SDK's own exception, so that everything the SDK throws
 * implements \Setono\MetaConversionsApi\Exception\ExceptionInterface
 *
 * @internal
 */
final class Assert extends \Webmozart\Assert\Assert
{
    /**
     * @param string $message
     */
    protected static function reportInvalidArgument($message): never
    {
        throw new InvalidArgumentException($message);
    }
}
