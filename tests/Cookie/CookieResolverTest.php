<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Cookie;

use PHPUnit\Framework\TestCase;

final class CookieResolverTest extends TestCase
{
    /**
     * @test
     */
    public function it_passes_through_existing_five_segment_cookies_unchanged(): void
    {
        $fbc = 'fb.1.1657051589577.IwAR1a-b_c.AQECAQMB';
        $fbp = 'fb.1.1656874832584.1088522659.AQEAAQMB';

        $resolvedCookies = (new CookieResolver())->resolve('www.example.com', [], ['_fbc' => $fbc, '_fbp' => $fbp]);

        self::assertNotNull($resolvedCookies->fbc);
        self::assertSame($fbc, $resolvedCookies->fbc->value());
        self::assertNotNull($resolvedCookies->fbp);
        self::assertSame($fbp, $resolvedCookies->fbp->value());
        self::assertSame([], $resolvedCookies->cookiesToSet);
    }

    /**
     * @test
     */
    public function it_upgrades_a_four_segment_cookie_with_an_appendix_and_sets_it(): void
    {
        $fbp = 'fb.1.1656874832584.1088522659';

        $resolvedCookies = (new CookieResolver())->resolve('www.example.com', [], ['_fbp' => $fbp]);

        self::assertNotNull($resolvedCookies->fbp);
        self::assertMatchesRegularExpression('/^fb\.1\.1656874832584\.1088522659\.[A-Za-z0-9_-]{8}$/', $resolvedCookies->fbp->value());
        self::assertNotNull($resolvedCookies->fbp->getAppendix());

        $cookie = self::cookie($resolvedCookies, '_fbp');
        self::assertSame($resolvedCookies->fbp->value(), $cookie->value);
        self::assertSame(90 * 24 * 3600, $cookie->maxAge);
        self::assertSame('example.com', $cookie->domain);
    }

    /**
     * @test
     */
    public function it_builds_an_fbc_from_the_fbclid_query_parameter(): void
    {
        $before = (int) floor(microtime(true) * 1000);
        $resolvedCookies = (new CookieResolver())->resolve('www.example.com', ['fbclid' => 'IwAR1a-b_c'], []);
        $after = (int) ceil(microtime(true) * 1000);

        self::assertNotNull($resolvedCookies->fbc);
        self::assertSame('IwAR1a-b_c', $resolvedCookies->fbc->getClickId());
        self::assertSame(1, $resolvedCookies->fbc->getSubdomainIndex());
        self::assertGreaterThanOrEqual($before, $resolvedCookies->fbc->getCreationTime());
        self::assertLessThanOrEqual($after, $resolvedCookies->fbc->getCreationTime());
        self::assertNotNull($resolvedCookies->fbc->getAppendix());

        self::assertSame($resolvedCookies->fbc->value(), self::cookie($resolvedCookies, '_fbc')->value);
    }

    /**
     * @test
     */
    public function it_generates_an_fbp_when_the_request_has_none(): void
    {
        $resolvedCookies = (new CookieResolver())->resolve('www.example.com', [], []);

        self::assertNull($resolvedCookies->fbc);
        self::assertNotNull($resolvedCookies->fbp);
        self::assertSame(1, $resolvedCookies->fbp->getSubdomainIndex());
        self::assertNotNull($resolvedCookies->fbp->getAppendix());

        self::assertSame($resolvedCookies->fbp->value(), self::cookie($resolvedCookies, '_fbp')->value);
    }

    /**
     * @test
     */
    public function it_regenerates_the_fbp_when_the_existing_cookie_has_an_invalid_appendix(): void
    {
        // a two character appendix must be one of the language tokens Meta supports, so ZZ is invalid
        $resolvedCookies = (new CookieResolver())->resolve('www.example.com', [], ['_fbp' => 'fb.1.1656874832584.1088522659.ZZ']);

        self::assertNotNull($resolvedCookies->fbp);
        self::assertGreaterThan(1656874832584, $resolvedCookies->fbp->getCreationTime());

        self::assertSame($resolvedCookies->fbp->value(), self::cookie($resolvedCookies, '_fbp')->value);
    }

    /**
     * @test
     */
    public function it_returns_null_for_a_cookie_that_cannot_be_represented_as_a_value_object(): void
    {
        // Meta's parameter builder only validates the segment count, so these pass through it,
        // but they are not valid fbc/fbp values
        $resolvedCookies = (new CookieResolver())->resolve('www.example.com', [], ['_fbc' => 'a.b.c.d', '_fbp' => 'e.f.g.h']);

        self::assertNull($resolvedCookies->fbc);
        self::assertNull($resolvedCookies->fbp);
        self::assertStringStartsWith('a.b.c.d.', self::cookie($resolvedCookies, '_fbc')->value);
        self::assertStringStartsWith('e.f.g.h.', self::cookie($resolvedCookies, '_fbp')->value);
    }

    /**
     * @test
     */
    public function it_uses_the_given_domains_to_derive_the_cookie_domain_and_subdomain_index(): void
    {
        $resolvedCookies = (new CookieResolver(['example.co.uk']))->resolve('shop.example.co.uk', [], []);

        self::assertNotNull($resolvedCookies->fbp);
        self::assertSame(2, $resolvedCookies->fbp->getSubdomainIndex());
        self::assertSame('example.co.uk', self::cookie($resolvedCookies, '_fbp')->domain);
    }

    private static function cookie(ResolvedCookies $resolvedCookies, string $name): Cookie
    {
        foreach ($resolvedCookies->cookiesToSet as $cookie) {
            if ($cookie->name === $name) {
                return $cookie;
            }
        }

        self::fail(sprintf('No cookie named "%s" was set', $name));
    }
}
