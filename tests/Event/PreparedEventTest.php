<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Pixel\Pixel;

final class PreparedEventTest extends TestCase
{
    /**
     * @test
     */
    public function it_round_trips_through_the_php_serializer(): void
    {
        $preparedEvent = new PreparedEvent(
            Event::EVENT_PURCHASE,
            'event_id',
            ['event_name' => 'Purchase', 'event_id' => 'event_id', 'user_data' => ['em' => ['hashed']]],
            [new Pixel('pixel_1', 'token_1'), new Pixel('pixel_2')],
            'TEST123',
        );

        $unserialized = unserialize(serialize($preparedEvent));

        self::assertInstanceOf(PreparedEvent::class, $unserialized);
        self::assertNotSame($preparedEvent, $unserialized);
        self::assertEquals($preparedEvent, $unserialized);
        self::assertSame('Purchase', $unserialized->eventName);
        self::assertSame('event_id', $unserialized->eventId);
        self::assertSame(['event_name' => 'Purchase', 'event_id' => 'event_id', 'user_data' => ['em' => ['hashed']]], $unserialized->payload);
        self::assertSame('TEST123', $unserialized->testEventCode);
        self::assertCount(2, $unserialized->pixels);
        self::assertSame('pixel_1', $unserialized->pixels[0]->id);
        self::assertSame('token_1', $unserialized->pixels[0]->accessToken);
        self::assertSame('pixel_2', $unserialized->pixels[1]->id);
        self::assertNull($unserialized->pixels[1]->accessToken);
    }

    /**
     * @test
     */
    public function it_has_no_test_event_code_by_default(): void
    {
        $preparedEvent = new PreparedEvent(Event::EVENT_PURCHASE, 'event_id', [], []);

        self::assertNull($preparedEvent->testEventCode);
        self::assertSame([], $preparedEvent->pixels);
    }

    /**
     * @test
     */
    public function it_removes_the_access_tokens(): void
    {
        $preparedEvent = new PreparedEvent(
            Event::EVENT_PURCHASE,
            'event_id',
            ['event_name' => 'Purchase'],
            [new Pixel('pixel_1', 'token_1'), new Pixel('pixel_2', 'token_2')],
            'TEST123',
        );

        $withoutAccessTokens = $preparedEvent->withoutAccessTokens();

        self::assertNotSame($preparedEvent, $withoutAccessTokens);
        self::assertEquals([new Pixel('pixel_1'), new Pixel('pixel_2')], $withoutAccessTokens->pixels);
        self::assertStringNotContainsString('token_1', serialize($withoutAccessTokens));

        // everything else is carried over
        self::assertSame('Purchase', $withoutAccessTokens->eventName);
        self::assertSame('event_id', $withoutAccessTokens->eventId);
        self::assertSame(['event_name' => 'Purchase'], $withoutAccessTokens->payload);
        self::assertSame('TEST123', $withoutAccessTokens->testEventCode);

        // the original is untouched
        self::assertSame('token_1', $preparedEvent->pixels[0]->accessToken);
        self::assertSame('token_2', $preparedEvent->pixels[1]->accessToken);
    }

    /**
     * @test
     */
    public function it_adds_the_access_tokens_by_pixel_id(): void
    {
        $preparedEvent = new PreparedEvent(
            Event::EVENT_PURCHASE,
            'event_id',
            ['event_name' => 'Purchase'],
            [new Pixel('1234567890'), new Pixel('pixel_2'), new Pixel('pixel_3', 'existing_token')],
            'TEST123',
        );

        // pixel ids are numeric strings in practice, which PHP turns into integer array keys
        $withAccessTokens = $preparedEvent->withAccessTokens(['1234567890' => 'token_1', 'pixel_2' => 'token_2']);

        self::assertNotSame($preparedEvent, $withAccessTokens);
        self::assertEquals(
            [new Pixel('1234567890', 'token_1'), new Pixel('pixel_2', 'token_2'), new Pixel('pixel_3', 'existing_token')],
            $withAccessTokens->pixels,
        );

        // everything else is carried over
        self::assertSame('Purchase', $withAccessTokens->eventName);
        self::assertSame('event_id', $withAccessTokens->eventId);
        self::assertSame(['event_name' => 'Purchase'], $withAccessTokens->payload);
        self::assertSame('TEST123', $withAccessTokens->testEventCode);

        // the original is untouched
        self::assertNull($preparedEvent->pixels[0]->accessToken);
        self::assertNull($preparedEvent->pixels[1]->accessToken);
    }

    /**
     * @test
     */
    public function it_replaces_an_existing_access_token(): void
    {
        $preparedEvent = new PreparedEvent(Event::EVENT_PURCHASE, 'event_id', [], [new Pixel('pixel_id', 'old_token')]);

        self::assertEquals([new Pixel('pixel_id', 'new_token')], $preparedEvent->withAccessTokens(['pixel_id' => 'new_token'])->pixels);
    }
}
