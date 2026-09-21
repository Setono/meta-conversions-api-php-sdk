<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use FacebookAds\ApiConfig;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Event\PreparedEvent;
use Setono\MetaConversionsApi\Exception\ExceptionInterface;
use Setono\MetaConversionsApi\Exception\InvalidArgumentException;
use Setono\MetaConversionsApi\Exception\ResponseException;
use Setono\MetaConversionsApi\Exception\TransportException;
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
        $event->pixels[] = new Pixel('pixel_id', 'access_token');
        $client->sendEvent($event);

        self::assertCount(1, $httpClient->requests);

        $request = $httpClient->requests[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame(sprintf('https://graph.facebook.com/v%s/pixel_id/events', ApiConfig::APIVersion), (string) $request->getUri());
        self::assertSame('access_token=access_token&data=%5B%7B%22event_name%22%3A%22Purchase%22%2C%22event_time%22%3A1658743659123%2C%22event_id%22%3A%22event_id%22%2C%22action_source%22%3A%22website%22%7D%5D', (string) $request->getBody());
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
            $event->pixels[] = new Pixel('pixel_id', 'access_token');

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
    public function it_sends_prepared_event(): void
    {
        $httpClient = new TestHttpClient();

        $client = new Client();
        $client->setHttpClient($httpClient);

        $preparedEvent = new PreparedEvent(
            Event::EVENT_PURCHASE,
            'event_id',
            ['event_name' => 'Purchase', 'event_time' => 1658743659123, 'event_id' => 'event_id', 'action_source' => 'website'],
            [new Pixel('pixel_1', 'token_1'), new Pixel('pixel_2', 'token_2')],
            'TEST123',
        );
        $client->sendPreparedEvent($preparedEvent);

        self::assertCount(2, $httpClient->requests);

        [$first, $second] = $httpClient->requests;
        self::assertSame('POST', $first->getMethod());
        self::assertSame(sprintf('https://graph.facebook.com/v%s/pixel_1/events', ApiConfig::APIVersion), (string) $first->getUri());
        self::assertSame(
            'access_token=token_1&data=%5B%7B%22event_name%22%3A%22Purchase%22%2C%22event_time%22%3A1658743659123%2C%22event_id%22%3A%22event_id%22%2C%22action_source%22%3A%22website%22%7D%5D&test_event_code=TEST123',
            (string) $first->getBody(),
        );
        self::assertSame(sprintf('https://graph.facebook.com/v%s/pixel_2/events', ApiConfig::APIVersion), (string) $second->getUri());
        self::assertStringContainsString('access_token=token_2', (string) $second->getBody());
    }

    /**
     * @test
     */
    public function it_sends_the_same_request_for_an_event_and_its_prepared_event(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->eventId = 'event_id';
        $event->eventTime = 1658743659123;
        $event->testEventCode = 'TEST123';
        $event->pixels[] = new Pixel('pixel_id', 'access_token');
        $event->userData->email[] = 'johndoe@example.com';

        $eventHttpClient = new TestHttpClient();
        $eventClient = new Client();
        $eventClient->setHttpClient($eventHttpClient);
        $eventClient->sendEvent($event);

        $preparedEventHttpClient = new TestHttpClient();
        $preparedEventClient = new Client();
        $preparedEventClient->setHttpClient($preparedEventHttpClient);
        $preparedEventClient->sendPreparedEvent($event->prepare());

        self::assertCount(1, $eventHttpClient->requests);
        self::assertCount(1, $preparedEventHttpClient->requests);
        self::assertSame((string) $eventHttpClient->requests[0]->getUri(), (string) $preparedEventHttpClient->requests[0]->getUri());
        self::assertSame((string) $eventHttpClient->requests[0]->getBody(), (string) $preparedEventHttpClient->requests[0]->getBody());
    }

    /**
     * @test
     */
    public function it_sends_a_prepared_event_that_was_queued_without_its_access_tokens(): void
    {
        $event = new Event(Event::EVENT_PURCHASE);
        $event->eventId = 'event_id';
        $event->eventTime = 1658743659123;
        $event->pixels[] = new Pixel('pixel_id', 'access_token');
        $event->userData->email[] = 'johndoe@example.com';

        $eventHttpClient = new TestHttpClient();
        $eventClient = new Client();
        $eventClient->setHttpClient($eventHttpClient);
        $eventClient->sendEvent($event);

        $queued = serialize($event->prepare()->withoutAccessTokens());
        self::assertStringNotContainsString('access_token', $queued);

        $preparedEvent = unserialize($queued);
        self::assertInstanceOf(PreparedEvent::class, $preparedEvent);

        $preparedEventHttpClient = new TestHttpClient();
        $preparedEventClient = new Client();
        $preparedEventClient->setHttpClient($preparedEventHttpClient);
        $preparedEventClient->sendPreparedEvent($preparedEvent->withAccessTokens(['pixel_id' => 'access_token']));

        self::assertCount(1, $preparedEventHttpClient->requests);
        self::assertSame((string) $eventHttpClient->requests[0]->getUri(), (string) $preparedEventHttpClient->requests[0]->getUri());
        self::assertSame((string) $eventHttpClient->requests[0]->getBody(), (string) $preparedEventHttpClient->requests[0]->getBody());
    }

    /**
     * @test
     */
    public function it_throws_when_a_pixel_has_no_access_token(): void
    {
        $httpClient = new TestHttpClient();

        $client = new Client();
        $client->setHttpClient($httpClient);

        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixels[] = new Pixel('pixel_id');

        try {
            $client->sendEvent($event);
            self::fail('Expected an InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('these pixels have no access token: pixel_id.', $e->getMessage());
        }

        self::assertCount(0, $httpClient->requests);
    }

    /**
     * @test
     */
    public function it_sends_nothing_when_any_of_the_pixels_has_no_access_token(): void
    {
        $httpClient = new TestHttpClient();

        $client = new Client();
        $client->setHttpClient($httpClient);

        $preparedEvent = new PreparedEvent(
            Event::EVENT_PURCHASE,
            'event_id',
            ['event_name' => 'Purchase'],
            [new Pixel('pixel_1', 'token_1'), new Pixel('pixel_2'), new Pixel('pixel_3', 'token_3'), new Pixel('pixel_4')],
        );

        try {
            // pixel_2 and pixel_4 were not in the list, so they are still without an access token
            $client->sendPreparedEvent($preparedEvent->withoutAccessTokens()->withAccessTokens(['pixel_1' => 'token_1', 'pixel_3' => 'token_3']));
            self::fail('Expected an InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('these pixels have no access token: pixel_2, pixel_4.', $e->getMessage());
        }

        // not even pixel_1, which comes first and has an access token, received the event
        self::assertCount(0, $httpClient->requests);
    }

    /**
     * @test
     */
    public function it_treats_an_empty_access_token_as_missing(): void
    {
        $httpClient = new TestHttpClient();

        $client = new Client();
        $client->setHttpClient($httpClient);

        // the constructor turns an empty string into null, but the property is public
        $pixel = new Pixel('pixel_id', 'access_token');
        $pixel->accessToken = '';

        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixels[] = $pixel;

        try {
            $client->sendEvent($event);
            self::fail('Expected an InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            self::assertStringContainsString('pixel_id', $e->getMessage());
        }

        self::assertCount(0, $httpClient->requests);
    }

    /**
     * @test
     */
    public function it_does_not_send_prepared_event_when_it_has_no_pixels(): void
    {
        $httpClient = new TestHttpClient();
        $logger = new TestLogger();

        $client = new Client();
        $client->setHttpClient($httpClient);
        $client->setLogger($logger);

        $client->sendPreparedEvent(new PreparedEvent(Event::EVENT_PURCHASE, 'event_id', [], []));

        self::assertCount(0, $httpClient->requests);
        self::assertTrue($logger->hasMessageMatching('#you haven\'n associated any pixels#'));
    }

    /**
     * @test
     */
    public function it_throws_a_response_exception_with_the_error_meta_reported(): void
    {
        $json = '{"error":{"message":"Invalid parameter","type":"OAuthException","code":100,"error_subcode":2804050,"is_transient":false,"fbtrace_id":"trace123"}}';
        $responseFactory = new Psr17Factory();

        $httpClient = new TestHttpClient();
        $httpClient->response = $responseFactory->createResponse(400)->withBody($responseFactory->createStream($json));

        $client = new Client();
        $client->setHttpClient($httpClient);

        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixels[] = new Pixel('pixel_id', 'access_token');

        try {
            $client->sendEvent($event);
            self::fail('Expected a ResponseException');
        } catch (ExceptionInterface $e) {
            self::assertInstanceOf(ResponseException::class, $e);
            self::assertSame(400, $e->statusCode);
            self::assertSame($json, $e->body);
            self::assertNotNull($e->errorResponse);
            self::assertSame('Invalid parameter', $e->errorResponse->message);
            self::assertSame(100, $e->errorResponse->code);
            self::assertSame(2804050, $e->errorResponse->subcode);
            self::assertFalse($e->errorResponse->transient);
            self::assertSame('trace123', $e->errorResponse->traceId);
            self::assertNull($e->getPrevious());
        }
    }

    /**
     * @test
     */
    public function it_throws_a_response_exception_when_the_body_is_not_in_metas_error_format(): void
    {
        $html = '<html><body>502 Bad Gateway</body></html>';
        $responseFactory = new Psr17Factory();

        $httpClient = new TestHttpClient();
        $httpClient->response = $responseFactory->createResponse(502)->withBody($responseFactory->createStream($html));

        $client = new Client();
        $client->setHttpClient($httpClient);

        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixels[] = new Pixel('pixel_id', 'access_token');

        try {
            $client->sendEvent($event);
            self::fail('Expected a ResponseException');
        } catch (ResponseException $e) {
            self::assertSame(502, $e->statusCode);
            self::assertSame($html, $e->body);
            self::assertNull($e->errorResponse);
            self::assertInstanceOf(InvalidArgumentException::class, $e->getPrevious());
        }
    }

    /**
     * @test
     */
    public function it_stops_at_the_first_pixel_that_gets_an_unsuccessful_response(): void
    {
        $responseFactory = new Psr17Factory();

        $httpClient = new TestHttpClient();
        $httpClient->response = $responseFactory->createResponse(500);

        $client = new Client();
        $client->setHttpClient($httpClient);

        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixels[] = new Pixel('pixel_1', 'token_1');
        $event->pixels[] = new Pixel('pixel_2', 'token_2');

        try {
            $client->sendEvent($event);
            self::fail('Expected a ResponseException');
        } catch (ResponseException $e) {
            self::assertSame(500, $e->statusCode);
        }

        self::assertCount(1, $httpClient->requests);
    }

    /**
     * @test
     */
    public function it_wraps_a_failure_of_the_http_client_in_a_transport_exception(): void
    {
        $httpClientException = new TestClientException('Connection timed out');

        $httpClient = new TestHttpClient();
        $httpClient->exception = $httpClientException;

        $client = new Client();
        $client->setHttpClient($httpClient);

        $event = new Event(Event::EVENT_PURCHASE);
        $event->pixels[] = new Pixel('pixel_id', 'access_token');

        try {
            $client->sendEvent($event);
            self::fail('Expected a TransportException');
        } catch (ExceptionInterface $e) {
            self::assertInstanceOf(TransportException::class, $e);
            self::assertSame('The request to Meta/Facebook failed: Connection timed out', $e->getMessage());
            self::assertSame($httpClientException, $e->getPrevious());
        }
    }

    /**
     * @test
     */
    public function it_throws_when_the_payload_cannot_be_encoded(): void
    {
        $httpClient = new TestHttpClient();

        $client = new Client();
        $client->setHttpClient($httpClient);

        // malformed UTF-8 cannot be JSON encoded
        $preparedEvent = new PreparedEvent(Event::EVENT_PURCHASE, 'event_id', ['custom' => "\xB1\x31"], [new Pixel('pixel_id', 'access_token')]);

        try {
            $client->sendPreparedEvent($preparedEvent);
            self::fail('Expected an InvalidArgumentException');
        } catch (ExceptionInterface $e) {
            self::assertInstanceOf(InvalidArgumentException::class, $e);
            self::assertStringContainsString('cannot be encoded as JSON', $e->getMessage());
            self::assertInstanceOf(\JsonException::class, $e->getPrevious());
        }

        self::assertCount(0, $httpClient->requests);
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

    public ?ClientExceptionInterface $exception = null;

    public function __construct()
    {
        $this->responseFactory = new Psr17Factory();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        if (null !== $this->exception) {
            throw $this->exception;
        }

        return $this->response ?? $this->responseFactory->createResponse();
    }
}

final class TestClientException extends \RuntimeException implements ClientExceptionInterface
{
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
