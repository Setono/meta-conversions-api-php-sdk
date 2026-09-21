<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Client;

use FacebookAds\ApiConfig;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\MetaConversionsApi\Event\Event;
use Setono\MetaConversionsApi\Event\PreparedEvent;
use Setono\MetaConversionsApi\Exception\InvalidArgumentException;
use Setono\MetaConversionsApi\Exception\ResponseException;
use Setono\MetaConversionsApi\Exception\TransportException;

final class Client implements ClientInterface, LoggerAwareInterface
{
    private ?HttpClientInterface $httpClient = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function sendEvent(Event $event): void
    {
        $this->sendPreparedEvent($event->prepare());
    }

    public function sendPreparedEvent(PreparedEvent $preparedEvent): void
    {
        if ([] === $preparedEvent->pixels) {
            $this->logger->error('You are trying to send events to Meta/Facebook, but you haven\'n associated any pixels with your event. This is most likely an error.');

            return;
        }

        // Meta rejects a request without an access token, and with an error that does not mention the token, so such
        // a pixel is never sent to. It is a legitimate state though, e.g. for a pixel that is only used in the browser,
        // and it must not keep the other pixels from receiving the event
        $pixels = [];
        $pixelIdsWithoutAccessToken = [];
        foreach ($preparedEvent->pixels as $pixel) {
            if (null === $pixel->accessToken || '' === $pixel->accessToken) {
                $pixelIdsWithoutAccessToken[] = $pixel->id;
            } else {
                $pixels[] = $pixel;
            }
        }

        if ([] === $pixels) {
            throw new InvalidArgumentException(sprintf(
                'The event was not sent to Meta/Facebook because none of its pixels has an access token: %s. If the access tokens were removed with PreparedEvent::withoutAccessTokens(), add them back with PreparedEvent::withAccessTokens() before sending',
                implode(', ', $pixelIdsWithoutAccessToken),
            ));
        }

        if ([] !== $pixelIdsWithoutAccessToken) {
            $this->logger->error(sprintf(
                'The event was not sent to these pixels because they have no access token: %s',
                implode(', ', $pixelIdsWithoutAccessToken),
            ));
        }

        $httpClient = $this->getHttpClient();
        $requestFactory = $this->getRequestFactory();

        try {
            $data = json_encode([$preparedEvent->payload], \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException(sprintf(
                'The event was not sent to Meta/Facebook because its payload cannot be encoded as JSON: %s',
                $e->getMessage(),
            ), previous: $e);
        }

        foreach ($pixels as $pixel) {
            $body = [
                'access_token' => $pixel->accessToken,
                'data' => $data,
            ];

            if (null !== $preparedEvent->testEventCode) {
                $body['test_event_code'] = $preparedEvent->testEventCode;
            }

            $request = $requestFactory->createRequest(
                'POST',
                sprintf('https://graph.facebook.com/%s/%s/events', sprintf('v%s', ApiConfig::APIVersion), $pixel->id),
            )
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->getStreamFactory()->createStream(http_build_query($body)));

            try {
                $response = $httpClient->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                throw new TransportException($e);
            }

            if ($response->getStatusCode() !== 200) {
                $body = (string) $response->getBody();

                try {
                    $errorResponse = ErrorResponse::fromJson($body);
                } catch (\InvalidArgumentException $e) {
                    throw new ResponseException($response->getStatusCode(), $body, null, $e);
                }

                throw new ResponseException($response->getStatusCode(), $body, $errorResponse);
            }
        }
    }

    private function getHttpClient(): HttpClientInterface
    {
        if (null === $this->httpClient) {
            $this->httpClient = Psr18ClientDiscovery::find();
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
            $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        }

        return $this->requestFactory;
    }

    public function setRequestFactory(RequestFactoryInterface $requestFactory): void
    {
        $this->requestFactory = $requestFactory;
    }

    private function getStreamFactory(): StreamFactoryInterface
    {
        if (null === $this->streamFactory) {
            $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
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
}
