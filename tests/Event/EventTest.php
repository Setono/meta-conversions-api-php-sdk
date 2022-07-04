<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    /**
     * @test
     */
    public function it_filters(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->eventTime = 123;
        $event->eventId = 'event_id';
        $event->userData->email[] = 'johndoe@example.com';
        /** @psalm-suppress PropertyTypeCoercion */
        $event->userData->email[] = '';
        /** @psalm-suppress PropertyTypeCoercion */
        $event->userData->email[] = null;
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $event->userData->dateOfBirth[] = \DateTimeImmutable::createFromFormat('Y-m-d', '1986-07-11');
        $event->customData->contents[] = new Content('content_id', 1);
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $event->customData->contents[] = null;

        self::assertEquals([
            'event_id' => 'event_id',
            'event_name' => 'purchase',
            'event_time' => 123,
            'custom_data' => [
                'contents' => [
                    ['id' => 'content_id', 'quantity' => 1],
                ],
            ],
            'user_data' => [
                'em' => [
                    '55e79200c1635b37ad31a378c39feb12f120f116625093a19bc32fff15041149',
                ],
                'db' => [
                    'cccd631dbe89ae6c982a960f248fabab8a4ae7f899853a3ea5bceef8ca1d6585',
                ],
            ],
            'action_source' => 'website',
        ], $event->normalize());
    }
}
