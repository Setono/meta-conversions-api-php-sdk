<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

/**
 * The same objects serialize differently depending on where the payload is going
 */
enum PayloadContext
{
    /**
     * The payload is sent to the Conversions API
     */
    case Server;

    /**
     * The payload is rendered into a page, e.g. in an fbq() call, so it must not contain the fields
     * that are only meant for the server: the IP address, the user agent, fbc and fbp
     */
    case Browser;
}
