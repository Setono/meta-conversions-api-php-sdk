<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Serializer;

use FacebookAds\Object\ServerSide\Util;
use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Event\Parameters;

final class SerializerTest extends TestCase
{
    /**
     * @test
     */
    public function it_serializes_array(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->eventTime = 10;
        $event->eventId = 'event_id';

        $email = 'johndoe@example.com';
        $expectedEmailHash = Util::hash($email);

        $event->userData->email[] = $email;

        self::assertSame(
            sprintf('[{"event_name":"purchase","event_time":10,"user_data":{"em":["%s"]},"event_id":"event_id","action_source":"website"}]', $expectedEmailHash),
            (new Serializer())->serialize([$event])
        );
    }

    /**
     * @test
     */
    public function it_returns_empty_object_if_input_is_empty(): void
    {
        $parameters = new class() extends Parameters {
            protected function getMapping(): array
            {
                return [];
            }
        };

        self::assertSame('{}', (new Serializer())->serialize($parameters));
    }
}
