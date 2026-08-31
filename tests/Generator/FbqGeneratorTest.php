<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Event\Parameters;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApi\TestLogger;

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

        $dateOfBirth = \DateTimeImmutable::createFromFormat('Y-m-d', '1986-07-11');
        self::assertNotFalse($dateOfBirth);
        $event->userData->dateOfBirth[] = $dateOfBirth;

        $generator = new FbqGenerator();
        self::assertSame(<<<EXPECTED
fbq('init', '111', {"db":["cccd631dbe89ae6c982a960f248fabab8a4ae7f899853a3ea5bceef8ca1d6585"]});fbq('init', '222', {"db":["cccd631dbe89ae6c982a960f248fabab8a4ae7f899853a3ea5bceef8ca1d6585"]});fbq('track', 'PageView');
EXPECTED
            , $generator->generateInit($event->pixels, $event->userData->getPayload(Parameters::PAYLOAD_CONTEXT_BROWSER), true, false));

        self::assertSame(<<<EXPECTED
<script>fbq('init', '111', {"db":["cccd631dbe89ae6c982a960f248fabab8a4ae7f899853a3ea5bceef8ca1d6585"]});fbq('init', '222', {"db":["cccd631dbe89ae6c982a960f248fabab8a4ae7f899853a3ea5bceef8ca1d6585"]});fbq('track', 'PageView');</script>
EXPECTED
            , $generator->generateInit($event->pixels, $event->userData->getPayload(Parameters::PAYLOAD_CONTEXT_BROWSER)));
    }

    /**
     * @test
     */
    public function it_generates_init_without_user_data(): void
    {
        $generator = new FbqGenerator();

        self::assertSame(
            "fbq('init', '111');fbq('init', '222');fbq('track', 'PageView');",
            $generator->generateInit([new Pixel('111'), new Pixel('222')], [], true, false),
        );
    }

    /**
     * @test
     */
    public function it_generates_init_without_the_page_view(): void
    {
        $generator = new FbqGenerator();

        self::assertSame(
            "fbq('init', '111');",
            $generator->generateInit([new Pixel('111')], [], false, false),
        );
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
            , $generator->generateTrack($event, false));

        self::assertSame(<<<EXPECTED
<script>fbq('track', 'Purchase', {"content_ids":["PROD_1","PROD_2"],"value":110.51}, {eventID: 'event_id'});</script>
EXPECTED
            , $generator->generateTrack($event));
    }

    /**
     * @test
     */
    public function it_generates_track_for_a_custom_event(): void
    {
        $event = new Event('MyCustomEvent');
        $event->eventId = 'event_id';
        $event->customData->value = 10.5;

        $generator = new FbqGenerator();
        self::assertSame(
            "fbq('trackCustom', 'MyCustomEvent', {\"value\":10.5}, {eventID: 'event_id'});",
            $generator->generateTrack($event, false),
        );
    }

    /**
     * @test
     */
    public function it_logs_an_error_and_returns_an_empty_string_when_the_user_data_cannot_be_encoded(): void
    {
        $logger = new TestLogger();

        $generator = new FbqGenerator();
        $generator->setLogger($logger);

        // malformed UTF-8 cannot be JSON encoded
        self::assertSame('', $generator->generateInit([new Pixel('111')], ['em' => "\xB1\x31"]));
        self::assertTrue($logger->hasMessageMatching('/Malformed UTF-8 characters/'));
    }
}
