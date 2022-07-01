<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

use Setono\MetaConversionsApi\Event\Event;

interface FbqGeneratorInterface
{
    /**
     * Will generate the fbq() init call based on the given event
     */
    public function generateInit(Event $event, string $pixelId, bool $includeScriptTag = false): string;

    /**
     * Will generate the fbq() tracking call based on the given event
     */
    public function generateTrack(Event $event, bool $includeScriptTag = false): string;
}
