<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use FacebookAds\ApiConfig;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Exception\ClientException;
use Setono\MetaConversionsApi\Pixel\Pixel;
use Setono\MetaConversionsApi\TestLogger;

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
        self::assertSame('https://graph.facebook.com/v25.0/pixel_id/events', (string) $request->getUri());
        self::assertSame('data=%5B%7B%22event_name%22%3A%22Purchase%22%2C%22event_time%22%3A1658743659123%2C%22event_id%22%3A%22event_id%22%2C%22action_source%22%3A%22website%22%7D%5D', (string) $request->getBody());
    }

    /**
     * @test
     */
    public function it_includes_access_token_and_test_event_code_in_the_request(): void
    {
        $httpClient = new TestHttpClient();

        $client = new Client();
        $client->setHttpClient($httpClient);

        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixels[] = new Pixel('pixel_id', 'access_token');
        $event->testEventCode = 'TEST123';
        $client->sendEvent($event);

        self::assertCount(1, $httpClient->requests);

        $body = (string) $httpClient->requests[0]->getBody();
        self::assertStringContainsString('access_token=access_token', $body);
        self::assertStringContainsString('test_event_code=TEST123', $body);
    }

    /**
     * @test
     */
    public function it_sends_one_request_per_pixel_using_the_injected_factories(): void
    {
        $httpClient = new TestHttpClient();
        $requestFactory = new TestRequestFactory();
        $streamFactory = new TestStreamFactory();

        $client = new Client();
        $client->setHttpClient($httpClient);
        $client->setRequestFactory($requestFactory);
        $client->setStreamFactory($streamFactory);

        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixels[] = new Pixel('pixel_1', 'token_1');
        $event->pixels[] = new Pixel('pixel_2', 'token_2');
        $client->sendEvent($event);

        self::assertSame(2, $requestFactory->calls);
        self::assertSame(2, $streamFactory->calls);
        self::assertCount(2, $httpClient->requests);

        [$first, $second] = $httpClient->requests;
        self::assertSame(sprintf('https://graph.facebook.com/v%s/pixel_1/events', ApiConfig::APIVersion), (string) $first->getUri());
        self::assertStringContainsString('access_token=token_1', (string) $first->getBody());
        self::assertSame(sprintf('https://graph.facebook.com/v%s/pixel_2/events', ApiConfig::APIVersion), (string) $second->getUri());
        self::assertStringContainsString('access_token=token_2', (string) $second->getBody());
    }

    /**
     * @test
     */
    public function it_discovers_an_http_client_when_none_is_injected(): void
    {
        $httpClient = new TestHttpClient();

        $strategies = [...Psr18ClientDiscovery::getStrategies()];
        TestDiscoveryStrategy::$httpClient = $httpClient;
        Psr18ClientDiscovery::prependStrategy(TestDiscoveryStrategy::class);

        try {
            $event = new Event(Event::EVENT_PURCHASE);
            $event->pixels[] = new Pixel('pixel_id');

            (new Client())->sendEvent($event);
        } finally {
            Psr18ClientDiscovery::setStrategies($strategies);
            TestDiscoveryStrategy::$httpClient = null;
        }

        self::assertCount(1, $httpClient->requests);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_response_is_not_successful(): void
    {
        $responseFactory = new Psr17Factory();

        $httpClient = new TestHttpClient();
        $httpClient->response = $responseFactory
            ->createResponse(400)
            ->withBody($responseFactory->createStream(
                '{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"fbtrace_id":"trace123"}}',
            ));

        $client = new Client();
        $client->setHttpClient($httpClient);

        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixels[] = new Pixel('pixel_id', 'access_token');

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Invalid parameter');

        $client->sendEvent($event);
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

    public ?ResponseInterface $response = null;

    public function __construct()
    {
        $this->responseFactory = new Psr17Factory();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        return $this->response ?? $this->responseFactory->createResponse();
    }
}

final class TestRequestFactory implements RequestFactoryInterface
{
    public int $calls = 0;

    private RequestFactoryInterface $decorated;

    public function __construct()
    {
        $this->decorated = new Psr17Factory();
    }

    /**
     * @param UriInterface|string $uri
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        ++$this->calls;

        return $this->decorated->createRequest($method, $uri);
    }
}

final class TestStreamFactory implements StreamFactoryInterface
{
    public int $calls = 0;

    private StreamFactoryInterface $decorated;

    public function __construct()
    {
        $this->decorated = new Psr17Factory();
    }

    public function createStream(string $content = ''): StreamInterface
    {
        ++$this->calls;

        return $this->decorated->createStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return $this->decorated->createStreamFromFile($filename, $mode);
    }

    /**
     * @param resource $resource
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return $this->decorated->createStreamFromResource($resource);
    }
}

/**
 * Lets the tests control what php-http/discovery finds, so that auto discovery can be tested without a real HTTP client
 */
final class TestDiscoveryStrategy implements DiscoveryStrategy
{
    public static ?HttpClientInterface $httpClient = null;

    /**
     * @param string $type
     *
     * @return list<array{class: \Closure(): HttpClientInterface}>
     */
    public static function getCandidates($type): array
    {
        if (HttpClientInterface::class !== $type || null === self::$httpClient) {
            return [];
        }

        $httpClient = self::$httpClient;

        return [['class' => static fn (): HttpClientInterface => $httpClient]];
    }
}
