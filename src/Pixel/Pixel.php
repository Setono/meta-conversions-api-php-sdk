<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Pixel;

final class Pixel
{
    public string $id;

    public ?string $accessToken;

    public function __construct(string $id, string $accessToken = null)
    {
        $this->id = $id;
        $this->accessToken = $accessToken;
    }

    public function __toString(): string
    {
        return $this->id;
    }
}
