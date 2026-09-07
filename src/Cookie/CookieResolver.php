<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Cookie;

use FacebookAds\CookieSettings;
use FacebookAds\ETLDPlus1Resolver;
use FacebookAds\ParamBuilder;
use Setono\MetaConversionsApi\ValueObject\Fbc;
use Setono\MetaConversionsApi\ValueObject\Fbp;
use Webmozart\Assert\Assert;

/**
 * Resolves the _fbc/_fbp cookies for a request by delegating to Meta's own parameter builder
 * (facebook/capi-param-builder-php), so this SDK does not have to replicate how Meta reads,
 * refreshes and writes those cookies: existing values are validated and upgraded to the current
 * format, a new fbc is built from the fbclid query parameter, and an fbp is generated when the
 * request has none
 */
final class CookieResolver implements CookieResolverInterface
{
    /** @var list<string>|ETLDPlus1Resolver|null */
    private array|ETLDPlus1Resolver|null $domains;

    /**
     * @param list<string>|ETLDPlus1Resolver|null $domains a list of your domains, used to derive the cookie domain
     *                                                     (e.g. ['example.co.uk']), or your own eTLD+1 resolver.
     *                                                     If null, the registrable domain is guessed from the host
     */
    public function __construct(array|ETLDPlus1Resolver|null $domains = null)
    {
        $this->domains = $domains;
    }

    public function resolve(
        string $host,
        array $query,
        array $cookies,
        ?string $referer = null,
        ?string $xForwardedFor = null,
        ?string $remoteAddress = null,
    ): ResolvedCookies {
        $paramBuilder = new ParamBuilder($this->domains);
        $paramBuilder->processRequest($host, $query, $cookies, $referer, $xForwardedFor, $remoteAddress);

        $fbc = $paramBuilder->getFbc();
        Assert::nullOrString($fbc);

        $fbp = $paramBuilder->getFbp();
        Assert::nullOrString($fbp);

        $cookieSettings = $paramBuilder->getCookiesToSet();
        Assert::isArray($cookieSettings);

        $cookiesToSet = [];
        foreach ($cookieSettings as $cookieSetting) {
            Assert::isInstanceOf($cookieSetting, CookieSettings::class);
            Assert::string($cookieSetting->name);
            Assert::string($cookieSetting->value);
            Assert::integer($cookieSetting->max_age);
            Assert::nullOrString($cookieSetting->domain);

            $cookiesToSet[] = new Cookie($cookieSetting->name, $cookieSetting->value, $cookieSetting->max_age, $cookieSetting->domain);
        }

        return new ResolvedCookies(
            null === $fbc ? null : self::parseFbc($fbc),
            null === $fbp ? null : self::parseFbp($fbp),
            $cookiesToSet,
        );
    }

    /**
     * Meta's parameter builder only validates the segment count and the appendix of an existing cookie,
     * so a malformed cookie can be passed through. Such a value cannot be represented as a value object
     * and is returned as null
     */
    private static function parseFbc(string $value): ?Fbc
    {
        try {
            return Fbc::fromString($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    private static function parseFbp(string $value): ?Fbp
    {
        try {
            return Fbp::fromString($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
