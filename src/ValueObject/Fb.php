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
     * The character set and the two lengths Meta's own parameter builder uses for the appendix
     *
     * @see https://github.com/facebook/capi-param-builder-php `APPENDIX_LENGTH_V1` and `APPENDIX_LENGTH_V2`
     */
    private const REGEXP_APPENDIX = '/^[A-Za-z0-9_-]{2,8}$/';

    /**
     * Creation time is the UNIX time since epoch in milliseconds when the _fbp cookie was saved
     */
    private int $creationTime;

    /**
     * The trailing segment Meta appends to the cookie value, e.g. the 'AQECAQMB' in
     * fb.1.1788781160733.IwAR1a-b_c.AQECAQMB
     *
     * We do not interpret it, but we do keep it: a value read from a cookie has to be written back unchanged,
     * otherwise every request rewrites the cookie into a different shape than the browser pixel expects
     */
    private ?string $appendix = null;

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

        Assert::integer($creationTime);
        Assert::greaterThanEq($creationTime, 1_075_590_000_000); // Facebooks founding date xD
        Assert::lessThanEq($creationTime, (time() + 1) * 1000);

        $obj = clone $this;
        $obj->creationTime = $creationTime;

        return $obj;
    }

    public function getCreationTimeAsDateTime(): \DateTimeImmutable
    {
        $dateTime = \DateTimeImmutable::createFromFormat('U.v', (string) ($this->creationTime / 1000));
        Assert::notFalse($dateTime);

        return $dateTime;
    }

    public function getAppendix(): ?string
    {
        return $this->appendix;
    }

    /**
     * @return static
     */
    public function withAppendix(?string $appendix): self
    {
        if (null !== $appendix) {
            Assert::regex($appendix, self::REGEXP_APPENDIX);
        }

        $obj = clone $this;
        $obj->appendix = $appendix;

        return $obj;
    }

    /**
     * Returns the appendix ready to be concatenated onto a value, i.e. '.AQECAQMB' or an empty string
     */
    final protected function appendixSuffix(): string
    {
        return null === $this->appendix ? '' : '.' . $this->appendix;
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
