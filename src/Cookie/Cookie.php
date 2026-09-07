<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Cookie;

/**
 * A cookie that should be set on the response, e.g. with setcookie() or your framework's response API
 */
final class Cookie
{
    public readonly string $name;

    public readonly string $value;

    /**
     * The max age in seconds
     */
    public readonly int $maxAge;

    /**
     * The registrable domain the cookie should be set on, e.g. 'example.com'. Null if it could not be derived
     */
    public readonly ?string $domain;

    public function __construct(string $name, string $value, int $maxAge, ?string $domain)
    {
        $this->name = $name;
        $this->value = $value;
        $this->maxAge = $maxAge;
        $this->domain = $domain;
    }
}
