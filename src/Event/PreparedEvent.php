<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use Setono\MetaConversionsApi\Pixel\Pixel;

/**
 * An event that is ready to be sent: the payload (normalized and hashed) together with the delivery information,
 * i.e. the pixels and the test event code. It holds no raw personal data, and everything in it is a scalar,
 * an array or a Pixel, so it serializes without any tricks.
 *
 * The pixels still carry their access tokens. Call withoutAccessTokens() before you store or queue the prepared event
 * if the storage should not hold them, and withAccessTokens() to add them back before sending
 *
 * @see Event::prepare()
 */
final class PreparedEvent
{
    /**
     * @param array<string, mixed> $payload the result of Event::getPayload()
     * @param list<Pixel> $pixels the pixels the event should be sent to
     */
    public function __construct(
        public readonly string $eventName,
        public readonly string $eventId,
        public readonly array $payload,
        public readonly array $pixels,
        public readonly ?string $testEventCode = null,
    ) {
    }

    /**
     * Returns a copy where the pixels carry no access tokens
     */
    public function withoutAccessTokens(): self
    {
        return new self(
            $this->eventName,
            $this->eventId,
            $this->payload,
            array_map(static fn (Pixel $pixel): Pixel => new Pixel($pixel->id), $this->pixels),
            $this->testEventCode,
        );
    }

    /**
     * Returns a copy where the pixels carry the given access tokens. Pixels that are not in the list are left as they are
     *
     * @param array<array-key, string> $accessTokens the access tokens indexed by pixel id
     */
    public function withAccessTokens(array $accessTokens): self
    {
        return new self(
            $this->eventName,
            $this->eventId,
            $this->payload,
            array_map(
                static fn (Pixel $pixel): Pixel => new Pixel($pixel->id, $accessTokens[$pixel->id] ?? $pixel->accessToken),
                $this->pixels,
            ),
            $this->testEventCode,
        );
    }
}
