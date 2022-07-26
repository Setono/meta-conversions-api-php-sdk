<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;

final class FbqGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates_init(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->eventId = 'event_id';
        $event->pixels = [new Pixel('111'), new Pixel('222')];
        $event->userData->clientIpAddress = '192.168.0.1';
        $event->userData->clientUserAgent = 'Chrome';
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $event->userData->dateOfBirth[] = \DateTimeImmutable::createFromFormat('Y-m-d', '1986-07-11');

        $generator = new FbqGenerator();
        self::assertSame(<<<EXPECTED
fbq('init', '111', {"db":["cccd631dbe89ae6c982a960f248fabab8a4ae7f899853a3ea5bceef8ca1d6585"]});fbq('init', '222', {"db":["cccd631dbe89ae6c982a960f248fabab8a4ae7f899853a3ea5bceef8ca1d6585"]});fbq('track', 'PageView');
EXPECTED
            , $generator->generateInit($event));

        self::assertSame(<<<EXPECTED
<script>fbq('init', '111', {"db":["cccd631dbe89ae6c982a960f248fabab8a4ae7f899853a3ea5bceef8ca1d6585"]});fbq('init', '222', {"db":["cccd631dbe89ae6c982a960f248fabab8a4ae7f899853a3ea5bceef8ca1d6585"]});fbq('track', 'PageView');</script>
EXPECTED
            , $generator->generateInit($event, true, true));
    }

    /**
     * @test
     */
    public function it_generates_track(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->eventId = 'event_id';
        $event->pixels = [new Pixel('111'), new Pixel('222')];
        $event->customData->value = 110.51;
        $event->customData->contentIds = ['PROD_1', 'PROD_2'];

        $generator = new FbqGenerator();
        self::assertSame(<<<EXPECTED
fbq('track', 'Purchase', {"content_ids":["PROD_1","PROD_2"],"value":110.51}, {eventID: 'event_id'});
EXPECTED
            , $generator->generateTrack($event));

        self::assertSame(<<<EXPECTED
<script>fbq('track', 'Purchase', {"content_ids":["PROD_1","PROD_2"],"value":110.51}, {eventID: 'event_id'});</script>
EXPECTED
            , $generator->generateTrack($event, true));
    }
}
