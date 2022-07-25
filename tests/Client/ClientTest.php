<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\AbstractLogger;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Pixel\Pixel;

/**
 * @covers \Setono\MetaConversionsApi\Client\Client
 */
final class ClientTest extends TestCase
{
    /**
     * @test
     */
    public function it_sends_event(): void
    {
        $httpClient = new TestHttpClient();

        $client = new Client();
        $client->setHttpClient($httpClient);

        $event = new Event(Event::EVENT_PURCHASE);
        $event->eventId = 'event_id';
        $event->eventTime = 1658743659123;
        $event->pixels[] = new Pixel('pixel_id');
        $client->sendEvent($event);

        self::assertCount(1, $httpClient->requests);

        $request = $httpClient->requests[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://graph.facebook.com/v13.0/pixel_id/events', (string) $request->getUri());
        self::assertSame('data=%5B%7B%22event_name%22%3A%22purchase%22%2C%22event_time%22%3A1658743659123%2C%22event_id%22%3A%22event_id%22%2C%22action_source%22%3A%22website%22%7D%5D', (string) $request->getBody());
    }

    /**
     * @test
     */
    public function it_does_not_send_event_when_event_has_no_pixels(): void
    {
        $httpClient = new TestHttpClient();
        $logger = new TestLogger();

        $client = new Client();
        $client->setHttpClient($httpClient);
        $client->setLogger($logger);

        $client->sendEvent(new Event(Event::EVENT_PURCHASE));

        self::assertCount(0, $httpClient->requests);
        self::assertTrue($logger->hasMessageMatching('#You are trying to send events to Meta/Facebook, but you haven\'n associated any pixels with your event\. This is most likely an error\.#'));
    }
}

final class TestHttpClient implements HttpClientInterface
{
    private ResponseFactoryInterface $responseFactory;

    /** @var list<RequestInterface> */
    public array $requests = [];

    public function __construct()
    {
        $this->responseFactory = new Psr17Factory();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return $this->responseFactory->createResponse();
    }
}

final class TestLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $messages = [];

    public function log($level, $message, array $context = [])
    {
        $this->messages[] = $message;
    }

    public function hasMessageMatching(string $regexp): bool
    {
        foreach ($this->messages as $message) {
            if (preg_match($regexp, $message) === 1) {
                return true;
            }
        }

        return false;
    }
}
