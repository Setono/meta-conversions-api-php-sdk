<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Event\Parameters;

final class FbqGenerator implements FbqGeneratorInterface, LoggerAwareInterface
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function generateInit(
        array $pixels,
        array $userData = [],
        bool $includePageView = true,
        bool $includeScriptTag = true
    ): string {
        try {
            $json = [] !== $userData ? json_encode($userData, \JSON_THROW_ON_ERROR) : null;
        } catch (\JsonException $e) {
            $this->logger->error($e->getMessage());

            return '';
        }

        $str = '';

        foreach ($pixels as $pixel) {
            $str .= null === $json ? sprintf("fbq('init', '%s');", $pixel->id) : sprintf("fbq('init', '%s', %s);", $pixel->id, $json);
        }

        if ($includePageView) {
            $str .= "fbq('track', 'PageView');";
        }

        if ($includeScriptTag) {
            $str = sprintf('<script>%s</script>', $str);
        }

        return $str;
    }

    public function generateTrack(Event $event, bool $includeScriptTag = true): string
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

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
