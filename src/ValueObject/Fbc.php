<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\ValueObject;

/**
 * See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc#fbc
 */
final class Fbc extends Fb
{
    /**
     * Click ids are base64url, so they contain - and _, and Meta appends an optional trailing segment
     *
     * Must match strings like:
     * - fb.1.1657051589577.IwAR0rmfgHgxjdKoEopat9y2SPzyjGgfHm9AhdqygToWvarP59nPq15T07MiA
     * - fb.1.1788781160733.IwAR1a-b_c.AQECAQMB
     */
    private const REGEXP_FBC = '/^fb\.([012])\.(\d{13})\.([A-Za-z0-9_-]+)(?:\.([A-Za-z0-9_-]{2,8}))?$/';

    private string $clickId;

    public function __construct(string $clickId)
    {
        parent::__construct();

        $this->clickId = $clickId;
    }

    public static function fromString(string $value): self
    {
        if (preg_match(self::REGEXP_FBC, $value, $matches) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                'The value "%s" didn\'t match the expected pattern for fbc: "%s"',
                $value,
                self::REGEXP_FBC,
            ));
        }

        return (new self($matches[3]))
            ->withSubdomainIndex((int) $matches[1])
            ->withCreationTime((int) $matches[2])
            ->withAppendix(($matches[4] ?? '') === '' ? null : $matches[4])
        ;
    }

    public function value(): string
    {
        return sprintf(
            'fb.%d.%d.%s%s',
            $this->getSubdomainIndex(),
            $this->getCreationTime(),
            $this->clickId,
            $this->appendixSuffix(),
        );
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
        $obj = clone $this;
        $obj->clickId = $clickId;

        return $obj;
    }
}
