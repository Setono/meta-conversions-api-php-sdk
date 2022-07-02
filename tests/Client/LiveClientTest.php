<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use PHPUnit\Framework\TestCase;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApi\Serializer\Serializer;
use Webmozart\Assert\Assert;

/**
 * Use this test to perform a live test against the Meta/Facebook API.
 * Set the needed environment variables in phpunit.xml
 */
final class LiveClientTest extends TestCase
{
    /**
     * @test
     */
    public function it_sends_event(): void
    {
        try {
            $testValues = $this->getTestValues();
        } catch (\InvalidArgumentException $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $serializer = new Serializer();
        $client = new Client($serializer);

        $event = new Event(Event::EVENT_VIEW_CONTENT);
        $event->eventSourceUrl = $testValues['url'];
        $event->userData->clientUserAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36';
        $event->userData->email = $testValues['email'];
        $event->pixels[] = new Pixel($testValues['pixelId'], $testValues['accessToken']);
        $event->testEventCode = $testValues['testEventCode'];

        $client->sendEvent($event);
    }

    /**
     * @return array{pixelId: string, testEventCode: string, accessToken: string, url: string, email: string }
     * @psalm-suppress InvalidReturnType
     */
    private function getTestValues(): array
    {
        $envVars = [
            'pixelId' => 'PIXEL_ID',
            'testEventCode' => 'TEST_EVENT_CODE',
            'accessToken' => 'ACCESS_TOKEN',
            'url' => 'URL',
            'email' => 'EMAIL',
        ];

        $values = [];

        foreach ($envVars as $variable => $envVar) {
            $value = getenv($envVar);
            Assert::stringNotEmpty($value, sprintf('%s environment value is not set', $envVar));

            $values[$variable] = $value;
        }

        /** @psalm-suppress InvalidReturnStatement */
        return $values;
    }
}
