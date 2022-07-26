<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Event\Parameters;

final class FbqGenerator implements FbqGeneratorInterface
{
    public function generateInit(Event $event, bool $includePageView = true, bool $includeScriptTag = false): string
    {
        $json = json_encode($event->userData->getPayload(Parameters::PAYLOAD_CONTEXT_BROWSER), \JSON_THROW_ON_ERROR);

        $str = '';

        foreach ($event->pixels as $pixel) {
            $str .= sprintf("fbq('init', '%s', %s);", $pixel->id, $json);
        }

        if ($includePageView) {
            $str .= "fbq('track', 'PageView');";
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
            json_encode($event->customData->getPayload(Parameters::PAYLOAD_CONTEXT_BROWSER), \JSON_THROW_ON_ERROR),
            $event->eventId
        );

        if ($includeScriptTag) {
            $str = sprintf('<script>%s</script>', $str);
        }

        return $str;
    }
}
