<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\ValueObject;

/**
 * See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc
 */
final class Fbp extends Fb
{
    public int $randomNumber;

    public function __construct()
    {
        parent::__construct();

        // from inspecting existing fbp values it seems that the random part is a number between 1,000,000,000 and 1,999,999,999
        $this->randomNumber = random_int(1_000_000_000, 1_999_999_999);
    }

    public static function fromString(string $value): self
    {
        // Must match something like this: fb.1.1656874832584.1088522659 or fb.1.1656874832584.1088522659.AQEAAQMB
        // NOTICE we match for 13 digits for the creation time. That number will be 14 digits in year 2286, so I guess it's safe to test for a specific number of digits ;)
        if (preg_match('/^fb\.([012])\.(\d{13})\.(\d+)(?:\.([A-Za-z0-9_-]{2,8}))?$/', $value, $matches) !== 1) {
            throw new \InvalidArgumentException(sprintf('The value "%s" didn\'t match the expected pattern for fbp', $value));
        }

        return (new self())
            ->withSubdomainIndex((int) $matches[1])
            ->withCreationTime((int) $matches[2])
            ->withRandomNumber((int) $matches[3])
            ->withAppendix(($matches[4] ?? '') === '' ? null : $matches[4])
        ;
    }

    public function value(): string
    {
        $value = sprintf('fb.%d.%d.%d', $this->getSubdomainIndex(), $this->getCreationTime(), $this->getRandomNumber());

        $appendix = $this->getAppendix();
        if (null !== $appendix) {
            $value .= '.' . $appendix;
        }

        return $value;
    }

    public function getRandomNumber(): int
    {
        return $this->randomNumber;
    }

    public function withRandomNumber(int $randomNumber): self
    {
        $obj = clone $this;
        $obj->randomNumber = $randomNumber;

        return $obj;
    }
}
