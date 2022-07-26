<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use Buzz\Client\Curl;
use Composer\InstalledVersions;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Exception\ClientException;
use Webmozart\Assert\Assert;

final class Client implements ClientInterface, LoggerAwareInterface
{
    private ?HttpClientInterface $httpClient = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?ResponseFactoryInterface $responseFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function sendEvent(Event $event): void
    {
        if (!$event->hasPixels()) {
            $this->logger->error('You are trying to send events to Meta/Facebook, but you haven\'n associated any pixels with your event. This is most likely an error.');

            return;
        }

        $httpClient = $this->getHttpClient();
        $requestFactory = $this->getRequestFactory();

        $data = json_encode([$event->getPayload()], \JSON_THROW_ON_ERROR);

        foreach ($event->pixels as $pixel) {
            $body = [
                'access_token' => $pixel->accessToken,
                'data' => $data,
            ];

            if (null !== $event->testEventCode) {
                $body['test_event_code'] = $event->testEventCode;
            }

            $request = $requestFactory->createRequest(
                'POST',
                sprintf('https://graph.facebook.com/%s/%s/events', self::getEndpointVersion(), $pixel->id)
            )
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->getStreamFactory()->createStream(http_build_query($body)));

            $response = $httpClient->sendRequest($request);

            if ($response->getStatusCode() !== 200) {
                throw ClientException::fromErrorResponse(ErrorResponse::fromJson((string) $response->getBody()));
            }
        }
    }

    private function getHttpClient(): HttpClientInterface
    {
        if (null === $this->httpClient) {
            if (!class_exists(Curl::class)) {
                throw ClientException::missingDependency(
                    Curl::class,
                    sprintf('Either set the http client with %s or run composer require kriswallsmith/buzz', self::class . '::setHttpClient()')
                );
            }

            $this->httpClient = new Curl($this->getResponseFactory());
        }

        return $this->httpClient;
    }

    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    private function getRequestFactory(): RequestFactoryInterface
    {
        if (null === $this->requestFactory) {
            if (!class_exists(Psr17Factory::class)) {
                throw ClientException::missingDependency(
                    Psr17Factory::class,
                    sprintf('Either set the request factory with %s or run composer require nyholm/psr7', self::class . '::setRequestFactory()')
                );
            }

            $this->requestFactory = new Psr17Factory();
        }

        return $this->requestFactory;
    }

    public function setRequestFactory(RequestFactoryInterface $requestFactory): void
    {
        $this->requestFactory = $requestFactory;
    }

    private function getResponseFactory(): ResponseFactoryInterface
    {
        if (null === $this->responseFactory) {
            if (!class_exists(Psr17Factory::class)) {
                throw ClientException::missingDependency(
                    Psr17Factory::class,
                    sprintf('Either set the response factory with %s or run composer require nyholm/psr7', self::class . '::setResponseFactory()')
                );
            }

            $this->responseFactory = new Psr17Factory();
        }

        return $this->responseFactory;
    }

    public function setResponseFactory(ResponseFactoryInterface $responseFactory): void
    {
        $this->responseFactory = $responseFactory;
    }

    private function getStreamFactory(): StreamFactoryInterface
    {
        if (null === $this->streamFactory) {
            if (!class_exists(Psr17Factory::class)) {
                throw ClientException::missingDependency(
                    Psr17Factory::class,
                    sprintf('Either set the stream factory with %s or run composer require nyholm/psr7', self::class . '::setStreamFactory()')
                );
            }

            $this->streamFactory = new Psr17Factory();
        }

        return $this->streamFactory;
    }

    public function setStreamFactory(StreamFactoryInterface $streamFactory): void
    {
        $this->streamFactory = $streamFactory;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private static function getEndpointVersion(): string
    {
        $version = InstalledVersions::getVersion('facebook/php-business-sdk');
        Assert::notNull($version);

        [$major, $minor] = explode('.', $version, 3);

        return sprintf('v%s.%s', $major, $minor);
    }
}
