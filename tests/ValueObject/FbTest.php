<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\ValueObject;

use PHPUnit\Framework\TestCase;

/**
 * Fb is abstract, so the behaviour it shares between Fbc and Fbp is tested through Fbp
 */
final class FbTest extends TestCase
{
    /**
     * @test
     */
    public function it_defaults_to_the_facebook_com_subdomain_index_and_the_current_time(): void
    {
        $before = (int) floor(microtime(true) * 1000);
        $fb = new Fbp();
        $after = (int) ceil(microtime(true) * 1000);

        self::assertSame(Fb::SUBDOMAIN_INDEX_FACEBOOK_COM, $fb->getSubdomainIndex());
        self::assertGreaterThanOrEqual($before, $fb->getCreationTime());
        self::assertLessThanOrEqual($after, $fb->getCreationTime());
    }

    /**
     * @test
     *
     * @dataProvider subdomainIndexes
     */
    public function it_has_an_immutable_subdomain_index_setter(int $subdomainIndex): void
    {
        $fb = new Fbp();
        $newFb = $fb->withSubdomainIndex($subdomainIndex);

        self::assertNotSame($fb, $newFb);
        self::assertSame(Fb::SUBDOMAIN_INDEX_FACEBOOK_COM, $fb->getSubdomainIndex());
        self::assertSame($subdomainIndex, $newFb->getSubdomainIndex());
        self::assertStringStartsWith(sprintf('fb.%d.', $subdomainIndex), $newFb->value());
    }

    /**
     * @return \Generator<string, array{int}>
     */
    public static function subdomainIndexes(): \Generator
    {
        yield 'com' => [Fb::SUBDOMAIN_INDEX_COM];
        yield 'facebook.com' => [Fb::SUBDOMAIN_INDEX_FACEBOOK_COM];
        yield 'www.facebook.com' => [Fb::SUBDOMAIN_INDEX_WWW_FACEBOOK_COM];
    }

    /**
     * @test
     *
     * @dataProvider invalidSubdomainIndexes
     */
    public function it_rejects_an_invalid_subdomain_index(int $subdomainIndex): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Fbp())->withSubdomainIndex($subdomainIndex);
    }

    /**
     * @return \Generator<array-key, array{int}>
     */
    public static function invalidSubdomainIndexes(): \Generator
    {
        yield [-1];
        yield [3];
    }

    /**
     * @test
     */
    public function it_accepts_a_datetime_as_creation_time(): void
    {
        $fb = (new Fbp())->withCreationTime(new \DateTimeImmutable('2022-07-03 19:00:32.584', new \DateTimeZone('UTC')));

        self::assertSame(1656874832584, $fb->getCreationTime());
        self::assertSame(1656874832, $fb->getCreationTimeAsSeconds());
        self::assertSame('2022-07-03 19:00:32.584', $fb->getCreationTimeAsDateTime()->format('Y-m-d H:i:s.v'));
    }

    /**
     * @test
     */
    public function it_accepts_creation_times_between_the_founding_of_facebook_and_now(): void
    {
        $fb = new Fbp();

        self::assertSame(1_075_590_000_000, $fb->withCreationTime(1_075_590_000_000)->getCreationTime());

        $now = time() * 1000;
        self::assertSame($now, $fb->withCreationTime($now)->getCreationTime());

        // the upper bound is one second into the future
        $oneSecondFromNow = (time() + 1) * 1000;
        self::assertSame($oneSecondFromNow, $fb->withCreationTime($oneSecondFromNow)->getCreationTime());
    }

    /**
     * @test
     *
     * @dataProvider creationTimesOutOfRange
     */
    public function it_rejects_a_creation_time_out_of_range(int $creationTime): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Fbp())->withCreationTime($creationTime);
    }

    /**
     * @return \Generator<string, array{int}>
     */
    public static function creationTimesOutOfRange(): \Generator
    {
        yield 'before Facebook was founded' => [1_075_589_999_999];
        yield 'in the future' => [(time() + 60) * 1000];
    }

    /**
     * @test
     */
    public function it_rejects_a_creation_time_that_is_neither_an_integer_nor_a_datetime(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new Fbp())->withCreationTime('1656874832584'); // @phpstan-ignore argument.type
    }
}
