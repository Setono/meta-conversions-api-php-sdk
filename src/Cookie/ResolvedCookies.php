<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Cookie;

use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApi\ValueObject\Fbp;

final class ResolvedCookies
{
    /**
     * Null when the request had no fbclid and no valid _fbc cookie
     */
    public readonly ?Fbc $fbc;

    /**
     * Null only when the _fbp cookie exists but cannot be represented as a value object
     */
    public readonly ?Fbp $fbp;

    /**
     * The cookies you should set on the response
     *
     * @var list<Cookie>
     */
    public readonly array $cookiesToSet;

    /**
     * @param list<Cookie> $cookiesToSet
     */
    public function __construct(?Fbc $fbc, ?Fbp $fbp, array $cookiesToSet)
    {
        $this->fbc = $fbc;
        $this->fbp = $fbp;
        $this->cookiesToSet = $cookiesToSet;
    }
}
