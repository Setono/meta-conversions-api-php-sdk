<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Event\PreparedEvent;
use Setono\MetaConversionsApi\Exception\ExceptionInterface;

/**
 * Implement this interface in a client that is able to send the conversion api event to a Meta/Facebook endpoint
 */
interface ClientInterface
{
    /**
     * @throws ExceptionInterface if the event's data is invalid, none of the pixels has an access token, or the request failed in any way
     */
    public function sendEvent(Event $event): void;

    /**
     * Sends an event that was prepared earlier with Event::prepare(). Use this when the personal data is hashed
     * at capture time and the event is sent later, for instance through a queue
     *
     * @throws ExceptionInterface if none of the pixels has an access token, the payload cannot be encoded, or the request failed in any way
     */
    public function sendPreparedEvent(PreparedEvent $preparedEvent): void;
}
