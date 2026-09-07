<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Cookie;

interface CookieResolverInterface
{
    /**
     * Takes the raw ingredients of an HTTP request and resolves the Meta cookies for it
     *
     * @param string $host the HTTP host of the current request, e.g. 'www.example.com'
     * @param array<array-key, mixed> $query the query parameters of the current request, e.g. $_GET
     * @param array<array-key, mixed> $cookies the cookies of the current request, e.g. $_COOKIE
     * @param string|null $referer the Referer header of the current request, if any
     * @param string|null $xForwardedFor the X-Forwarded-For header of the current request, if any
     * @param string|null $remoteAddress the remote address of the current request, if any
     */
    public function resolve(
        string $host,
        array $query,
        array $cookies,
        ?string $referer = null,
        ?string $xForwardedFor = null,
        ?string $remoteAddress = null,
    ): ResolvedCookies;
}
