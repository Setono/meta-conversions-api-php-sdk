<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Event\PreparedEvent;
use Setono\MetaConversionsApi\Exception\ClientException;

/**
 * Implement this interface in a client that is able to send the conversion api event to a Meta/Facebook endpoint
 */
interface ClientInterface
{
    /**
     * @throws ClientException if a pixel has no access token or the request failed in any way
     */
    public function sendEvent(Event $event): void;

    /**
     * Sends an event that was prepared earlier with Event::prepare(). Use this when the personal data is hashed
     * at capture time and the event is sent later, for instance through a queue
     *
     * @throws ClientException if a pixel has no access token or the request failed in any way
     */
    public function sendPreparedEvent(PreparedEvent $preparedEvent): void;
}
