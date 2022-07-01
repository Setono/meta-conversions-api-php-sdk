<?php
declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use Setono\MetaConversionsApi\Event\Event;

/**
 * Implement this interface in a client that is able to send the conversion api event to a Facebook endpoint
 */
interface ClientInterface
{
    public function sendEvent(Event $event): void;
}
