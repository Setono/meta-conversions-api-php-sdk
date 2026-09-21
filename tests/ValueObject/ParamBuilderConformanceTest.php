<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\ValueObject;

use FacebookAds\ParamBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Uses Meta's own parameter builder (facebook/capi-param-builder-php, a dev dependency) as an oracle:
 * every _fbc and _fbp value the builder writes must be parsed by the value objects and written back byte for byte.
 *
 * The check is deliberately one-directional. The builder's own parser is only structural, it accepts 'a.b.c.d',
 * so the value objects stay stricter than the builder. They are also more lenient in one place: the builder only
 * accepts six language tokens as a two-character appendix, the value objects accept any.
 *
 * What these tests cannot prove is agreement with the browser pixel, which writes most cookies in the wild.
 */
final class ParamBuilderConformanceTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider clickIds
     */
    public function it_parses_the_fbc_the_builder_creates_from_an_fbclid(string $clickId): void
    {
        $before = (int) floor(microtime(true) * 1000);
        $value = self::fbc('www.example.com', ['fbclid' => $clickId], []);
        $after = (int) ceil(microtime(true) * 1000);

        $fbc = Fbc::fromString($value);

        self::assertSame($value, $fbc->value());
        self::assertSame($clickId, $fbc->getClickId());
        self::assertSame(1, $fbc->getSubdomainIndex());
        self::assertGreaterThanOrEqual($before, $fbc->getCreationTime());
        self::assertLessThanOrEqual($after, $fbc->getCreationTime());
        self::assertNotNull($fbc->getAppendix());
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function clickIds(): \Generator
    {
        yield 'letters and digits' => ['IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA'];
        yield 'base64url with a dash and an underscore' => ['IwZXh0bgNhZW0CMTAAAR-uK_5w'];
        yield 'ending in an underscore' => ['IwY2xjawMxabc123_'];
        yield 'short' => ['abc'];
    }

    /**
     * @test
     */
    public function it_parses_the_fbc_the_builder_writes_when_the_fbclid_changes(): void
    {
        $value = self::fbc('www.example.com', ['fbclid' => 'NewClickId'], ['_fbc' => 'fb.1.1657051589577.OldClickId.AQECAQMB']);

        $fbc = Fbc::fromString($value);

        self::assertSame($value, $fbc->value());
        self::assertSame('NewClickId', $fbc->getClickId());
        self::assertGreaterThan(1657051589577, $fbc->getCreationTime());
    }

    /**
     * @test
     *
     * @dataProvider hosts
     *
     * @param list<string>|null $domains
     */
    public function it_parses_the_fbp_the_builder_generates(string $host, ?array $domains, int $expectedSubdomainIndex): void
    {
        // the builder generates a random number of varying length, so a few rounds exercise more than one shape
        for ($i = 0; $i < 20; ++$i) {
            $value = self::fbp($host, [], [], $domains);

            $fbp = Fbp::fromString($value);

            self::assertSame($value, $fbp->value());
            self::assertSame($expectedSubdomainIndex, $fbp->getSubdomainIndex());
            self::assertSame(explode('.', $value)[3], (string) $fbp->getRandomNumber());
            self::assertNotNull($fbp->getAppendix());
        }
    }

    /**
     * @test
     *
     * @dataProvider hosts
     *
     * @param list<string>|null $domains
     */
    public function it_agrees_with_the_builder_on_the_subdomain_index_of_an_fbc(string $host, ?array $domains, int $expectedSubdomainIndex): void
    {
        $value = self::fbc($host, ['fbclid' => 'ClickId'], [], $domains);

        $fbc = Fbc::fromString($value);

        self::assertSame($value, $fbc->value());
        self::assertSame($expectedSubdomainIndex, $fbc->getSubdomainIndex());
    }

    /**
     * @return \Generator<string, array{string, list<string>|null, int}>
     */
    public static function hosts(): \Generator
    {
        yield 'a registrable domain' => ['example.com', null, 1];
        yield 'a subdomain' => ['www.example.com', null, 1];
        yield 'a host with a port' => ['www.example.com:8443', null, 1];
        yield 'a two-label public suffix, given the list of domains' => ['shop.example.co.uk', ['example.co.uk'], 2];
        yield 'a deep host without a list of domains' => ['a.b.example.co.uk', null, 3];
        yield 'a host without dots' => ['localhost', null, 0];
        yield 'an ip address' => ['127.0.0.1', null, 0];
    }

    /**
     * @test
     */
    public function it_parses_the_legacy_cookies_the_builder_upgrades_with_an_appendix(): void
    {
        $cookies = ['_fbc' => 'fb.1.1657051589577.ClickId', '_fbp' => 'fb.1.1656874832584.1088522659'];

        $fbcValue = self::fbc('www.example.com', [], $cookies);
        $fbpValue = self::fbp('www.example.com', [], $cookies);

        self::assertStringStartsWith('fb.1.1657051589577.ClickId.', $fbcValue);
        self::assertStringStartsWith('fb.1.1656874832584.1088522659.', $fbpValue);

        $fbc = Fbc::fromString($fbcValue);
        self::assertSame($fbcValue, $fbc->value());
        self::assertSame(1657051589577, $fbc->getCreationTime());
        self::assertSame('ClickId', $fbc->getClickId());
        self::assertSame(substr($fbcValue, (int) strrpos($fbcValue, '.') + 1), $fbc->getAppendix());

        $fbp = Fbp::fromString($fbpValue);
        self::assertSame($fbpValue, $fbp->value());
        self::assertSame(1656874832584, $fbp->getCreationTime());
        self::assertSame(1088522659, $fbp->getRandomNumber());
        self::assertSame(substr($fbpValue, (int) strrpos($fbpValue, '.') + 1), $fbp->getAppendix());
    }

    /**
     * @test
     *
     * @dataProvider appendixes
     */
    public function it_parses_the_cookies_the_builder_passes_through_unchanged(string $appendix): void
    {
        $cookies = [
            '_fbc' => sprintf('fb.1.1657051589577.ClickId.%s', $appendix),
            '_fbp' => sprintf('fb.1.1656874832584.1088522659.%s', $appendix),
        ];

        // the builder accepts them as they are ...
        self::assertSame($cookies['_fbc'], self::fbc('www.example.com', [], $cookies));
        self::assertSame($cookies['_fbp'], self::fbp('www.example.com', [], $cookies));

        // ... and so do we
        self::assertSame($cookies['_fbc'], Fbc::fromString($cookies['_fbc'])->value());
        self::assertSame($appendix, Fbc::fromString($cookies['_fbc'])->getAppendix());
        self::assertSame($cookies['_fbp'], Fbp::fromString($cookies['_fbp'])->value());
        self::assertSame($appendix, Fbp::fromString($cookies['_fbp'])->getAppendix());
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function appendixes(): \Generator
    {
        yield 'eight characters: a new value' => ['AQECAQMB'];
        yield 'eight characters: an unchanged value' => ['AQEAAQMB'];
        yield 'eight characters: a modified value' => ['AQEDAQMB'];

        // the two-character language tokens written by earlier versions of Meta's builders
        foreach (['AQ', 'Ag', 'Aw', 'BA', 'BQ', 'Bg'] as $languageToken) {
            yield sprintf('the language token %s', $languageToken) => [$languageToken];
        }
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $cookies
     * @param list<string>|null $domains
     */
    private static function fbc(string $host, array $query, array $cookies, ?array $domains = null): string
    {
        $value = self::process($host, $query, $cookies, $domains)->getFbc();
        self::assertIsString($value);

        return $value;
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $cookies
     * @param list<string>|null $domains
     */
    private static function fbp(string $host, array $query, array $cookies, ?array $domains = null): string
    {
        $value = self::process($host, $query, $cookies, $domains)->getFbp();
        self::assertIsString($value);

        return $value;
    }

    /**
     * @param array<string, string> $query
     * @param array<string, string> $cookies
     * @param list<string>|null $domains
     */
    private static function process(string $host, array $query, array $cookies, ?array $domains): ParamBuilder
    {
        $paramBuilder = new ParamBuilder($domains);
        $paramBuilder->processRequest($host, $query, $cookies);

        return $paramBuilder;
    }
}
