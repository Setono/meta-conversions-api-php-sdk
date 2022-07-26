<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Exception\ClientException;

/**
 * Implement this interface in a client that is able to send the conversion api event to a Meta/Facebook endpoint
 */
interface ClientInterface
{
    /**
     * @throws ClientException if the request failed in any way
     */
    public function sendEvent(Event $event): void;
}
