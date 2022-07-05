<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\ValueObject;

use Webmozart\Assert\Assert;

abstract class Fb
{
    public const SUBDOMAIN_INDEX_COM = 0;

    public const SUBDOMAIN_INDEX_FACEBOOK_COM = 1;

    public const SUBDOMAIN_INDEX_WWW_FACEBOOK_COM = 2;

    private int $subdomainIndex = self::SUBDOMAIN_INDEX_FACEBOOK_COM;

    /**
     * Creation time is the UNIX time since epoch in milliseconds when the _fbp cookie was saved
     */
    private int $creationTime;

    public function __construct()
    {
        $this->creationTime = (int) ceil(microtime(true) * 1000);
    }

    /**
     * @throws \InvalidArgumentException if the $value is not the correct format
     */
    abstract public static function fromString(string $value): self;

    abstract public function value(): string;

    public function getSubdomainIndex(): int
    {
        return $this->subdomainIndex;
    }

    /**
     * @return static
     */
    public function withSubdomainIndex(int $subdomainIndex): self
    {
        Assert::oneOf($subdomainIndex, [
            self::SUBDOMAIN_INDEX_COM,
            self::SUBDOMAIN_INDEX_FACEBOOK_COM,
            self::SUBDOMAIN_INDEX_WWW_FACEBOOK_COM,
        ]);

        $obj = clone $this;
        $obj->subdomainIndex = $subdomainIndex;

        return $obj;
    }

    public function getCreationTime(): int
    {
        return $this->creationTime;
    }

    /**
     * @param int|\DateTimeInterface $creationTime
     *
     * @return static
     */
    public function withCreationTime($creationTime): self
    {
        if ($creationTime instanceof \DateTimeInterface) {
            $creationTime = (int) $creationTime->format('Uv');
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        Assert::integer($creationTime);
        Assert::greaterThanEq($creationTime, 1_075_590_000_000); // Facebooks founding date xD
        Assert::lessThanEq($creationTime, (time() + 1) * 1000);

        $obj = clone $this;
        $obj->creationTime = $creationTime instanceof \DateTimeInterface ? (int) $creationTime->format('Uv') : $creationTime;

        return $obj;
    }

    public function getCreationTimeAsDateTime(): \DateTimeImmutable
    {
        $dateTime = \DateTimeImmutable::createFromFormat('Uv', (string) $this->creationTime);
        Assert::notFalse($dateTime);

        return $dateTime;
    }

    public function getCreationTimeAsSeconds(): int
    {
        return (int) ($this->creationTime / 1000);
    }

    public function __toString(): string
    {
        return $this->value();
    }
}
