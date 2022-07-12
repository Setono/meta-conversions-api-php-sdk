<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

use Setono\MetaConversionsApi\Event\Event;

final class FbqGenerator implements FbqGeneratorInterface
{
    public function generateInit(Event $event, bool $includeScriptTag = false): string
    {
        $json = json_encode($event->userData, \JSON_THROW_ON_ERROR);

        $str = '';

        foreach ($event->pixels as $pixel) {
            $str .= sprintf("fbq('init', '%s', %s);", $pixel->id, $json);
        }

        if ($includeScriptTag) {
            $str = sprintf('<script>%s</script>', $str);
        }

        return $str;
    }

    public function generateTrack(Event $event, bool $includeScriptTag = false): string
    {
        $str = sprintf(
            "fbq('%s', '%s', %s, {eventID: '%s'});",
            $event->isCustom() ? 'trackCustom' : 'track',
            $event->eventName,
            json_encode($event->customData, \JSON_THROW_ON_ERROR),
            $event->eventId
        );

        if ($includeScriptTag) {
            $str = sprintf('<script>%s</script>', $str);
        }

        return $str;
    }
}
