<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

use Setono\MetaConversionsApi\Event\Event;

interface FbqGeneratorInterface
{
    /**
     * Will generate the fbq() init call based on the given event and for each pixel ids defined in the event
     */
    public function generateInit(Event $event, bool $includePageView = true, bool $includeScriptTag = false): string;

    /**
     * Will generate the fbq() tracking call based on the given event and for each pixel ids defined in the event
     */
    public function generateTrack(Event $event, bool $includeScriptTag = false): string;
}
