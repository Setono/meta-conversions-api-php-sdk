<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use Buzz\Client\Curl;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Exception\ClientException;

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

        $data = json_encode([$event], \JSON_THROW_ON_ERROR);

        foreach ($event->pixels as $pixel) {
            $body = [
                'access_token' => $pixel->accessToken,
                'data' => $data,
            ];

            if (null !== $event->testEventCode) {
                $body['test_event_code'] = $event->testEventCode;
            }

            $stream = $this->getStreamFactory()->createStream(http_build_query($body));

            $request = $requestFactory->createRequest(
                'POST',
                sprintf('https://graph.facebook.com/v13.0/%s/events', $pixel->id)
            )
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json')
            ->withBody($stream);

            $this->handleResponse($httpClient->sendRequest($request));
        }
    }

    private function handleResponse(ResponseInterface $response): void
    {
        if ($response->getStatusCode() === 200) {
            return;
        }

        throw ClientException::fromErrorResponse(ErrorResponse::fromJson((string) $response->getBody()));
    }

    private function getHttpClient(): HttpClientInterface
    {
        if (null === $this->httpClient) {
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
        // TODO: Implement setLogger() method.
    }
}
