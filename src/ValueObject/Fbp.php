<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\ValueObject;

use Webmozart\Assert\Assert;

/**
 * See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc
 */
final class Fbp
{
    public const SUBDOMAIN_INDEX_COM = 0;

    public const SUBDOMAIN_INDEX_FACEBOOK_COM = 1;

    public const SUBDOMAIN_INDEX_WWW_FACEBOOK_COM = 2;

    public int $subdomainIndex;

    /**
     * Creation time is the UNIX time since epoch in milliseconds when the _fbp cookie was saved
     */
    public int $creationTime;

    public int $randomNumber;

    public function __construct(
        int $subdomainIndex = self::SUBDOMAIN_INDEX_FACEBOOK_COM,
        int $creationTime = null,
        int $randomNumber = null
    ) {
        $this->subdomainIndex = $subdomainIndex;
        $this->creationTime = $creationTime ?? (int) ceil(microtime(true) * 1000);

        // from inspecting existing fbp values it seems that the random part is a number between 1,000,000,000 and 1,999,999,999
        $this->randomNumber = $randomNumber ?? random_int(1_000_000_000, 1_999_999_999);
    }

    public static function fromString(string $value): self
    {
        [, $subdomainIndex, $creationTime, $randomNumber] = explode('.', $value);

        return new self((int) $subdomainIndex, (int) $creationTime, (int) $randomNumber);
    }

    public function value(): string
    {
        return sprintf('fb.%d.%d.%d', $this->subdomainIndex, $this->creationTime, $this->randomNumber);
    }

    public function creationTimeAsDateTime(): \DateTimeImmutable
    {
        $dateTime = \DateTimeImmutable::createFromFormat('Uv', (string) $this->creationTime);
        Assert::notFalse($dateTime);

        return $dateTime;
    }

    public function creationTimeAsSeconds(): int
    {
        return (int) ($this->creationTime / 1000);
    }

    public function __toString(): string
    {
        return $this->value();
    }
}
