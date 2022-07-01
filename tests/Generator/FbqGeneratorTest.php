<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;

final class FbqGeneratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates_init(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixelIds = ['111', '222'];
        $event->userData->clientIpAddress = '192.168.0.1';
        $event->userData->clientUserAgent = 'Chrome';

        $generator = new FbqGenerator();
        self::assertSame(<<<EXPECTED
fbq('init', '111', {"client_ip_address":"192.168.0.1","client_user_agent":"Chrome"});fbq('init', '222', {"client_ip_address":"192.168.0.1","client_user_agent":"Chrome"});
EXPECTED
            , $generator->generateInit($event));

        self::assertSame(<<<EXPECTED
<script>fbq('init', '111', {"client_ip_address":"192.168.0.1","client_user_agent":"Chrome"});fbq('init', '222', {"client_ip_address":"192.168.0.1","client_user_agent":"Chrome"});</script>
EXPECTED
            , $generator->generateInit($event, true));
    }

    /**
     * @test
     */
    public function it_generates_track(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixelIds = ['111', '222'];
        $event->customData->value = 110.51;
        $event->customData->contentIds = ['PROD_1', 'PROD_2'];

        $generator = new FbqGenerator();
        self::assertSame(<<<EXPECTED
fbq('track', 'Purchase', {"content_ids":{"0":"PROD_1","1":"PROD_2"},"value":110.51});fbq('track', 'Purchase', {"content_ids":{"0":"PROD_1","1":"PROD_2"},"value":110.51});
EXPECTED
            , $generator->generateTrack($event));

        self::assertSame(<<<EXPECTED
<script>fbq('track', 'Purchase', {"content_ids":{"0":"PROD_1","1":"PROD_2"},"value":110.51});fbq('track', 'Purchase', {"content_ids":{"0":"PROD_1","1":"PROD_2"},"value":110.51});</script>
EXPECTED
            , $generator->generateTrack($event, true));
    }
}
