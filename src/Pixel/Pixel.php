<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Pixel;

final class Pixel
{
    public string $id;

    /**
     * The reasons for the access token being nullable:
     *
     * 1. If you only want to use client side tracking you don't need an access token
     * 2. It's not necessarily the case that the access token is present when you create the event,
     *    but first available when you want to send the event, hence you populate it later
     * 3. There's the risk of having the access token being outputted in some user facing error message
     */
    public ?string $accessToken;

    public function __construct(string $id, string $accessToken = null)
    {
        $this->id = $id;
        $this->accessToken = '' === $accessToken ? null : $accessToken;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
