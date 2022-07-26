<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;

interface FbqGeneratorInterface
{
    /**
     * Will generate the fbq() init call based on the given pixels. By default, this also includes the page view event
     *
     * @param list<Pixel> $pixels
     */
    public function generateInit(
        array $pixels,
        array $userData = [],
        bool $includePageView = true,
        bool $includeScriptTag = true
    ): string;

    /**
     * Will generate the fbq() tracking call based on the given event and for each pixel ids defined in the event
     */
    public function generateTrack(Event $event, bool $includeScriptTag = true): string;
}
