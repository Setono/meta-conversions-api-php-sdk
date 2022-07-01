<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Serializer\Serializer;
use Setono\MetaConversionsApi\Serializer\SerializerInterface;

final class FbqGenerator implements FbqGeneratorInterface
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer ?? new Serializer();
    }

    public function generateInit(Event $event, bool $includeScriptTag = false): string
    {
        $json = $this->serializer->serialize($event->userData);

        $str = '';

        foreach ($event->pixels as $pixel) {
            $str .= sprintf("fbq('init', '%s', %s);", (string) $pixel, $json);
        }

        if ($includeScriptTag) {
            $str = sprintf('<script>%s</script>', $str);
        }

        return $str;
    }

    public function generateTrack(Event $event, bool $includeScriptTag = false): string
    {
        $json = $this->serializer->serialize($event->customData);

        $str = '';

        foreach ($event->pixels as $_) {
            $str .= sprintf(
                "fbq('%s', '%s', %s);",
                $event->isCustom() ? 'trackCustom' : 'track',
                $event->eventName,
                $json
            );
        }

        if ($includeScriptTag) {
            $str = sprintf('<script>%s</script>', $str);
        }

        return $str;
    }
}
