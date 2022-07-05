<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\ValueObject;

use Webmozart\Assert\Assert;

/**
 * See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc#fbc
 */
final class Fbc extends Fb
{
    private const REGEXP_CLICK_ID = '/^fb\.([012])\.(\d{13})\.([a-zA-Z0-9]+)$/';

    private string $clickId;

    public function __construct(string $clickId)
    {
        parent::__construct();

        $this->clickId = $clickId;
    }

    public static function fromString(string $value): self
    {
        // Must match strings like: fb.1.1657051589577.IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA
        if (preg_match(self::REGEXP_CLICK_ID, $value, $matches) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                'The value "%s" didn\'t match the expected pattern for fbc: "%s"',
                $value,
                self::REGEXP_CLICK_ID
            ));
        }

        return (new self($matches[3]))
            ->withSubdomainIndex((int) $matches[1])
            ->withCreationTime((int) $matches[2])
        ;
    }

    public function value(): string
    {
        return sprintf('fb.%d.%d.%d', $this->getSubdomainIndex(), $this->getCreationTime(), $this->clickId);
    }

    /**
     * This is the facebook click id (i.e. fbclid query parameter)
     */
    public function getClickId(): string
    {
        return $this->clickId;
    }

    public function withClickId(string $clickId): self
    {
        Assert::regex($clickId, self::REGEXP_CLICK_ID);

        $obj = clone $this;
        $obj->clickId = $clickId;

        return $obj;
    }
}
