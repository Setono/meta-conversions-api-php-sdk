<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use FacebookAds\Object\ServerSide\Normalizer;
use FacebookAds\Object\ServerSide\Util;
use JsonSerializable;
use Webmozart\Assert\Assert;

abstract class Parameters implements JsonSerializable
{
    /**
     * This method returns an array representation of the object ready
     * to be sent to Meta/Facebook, i.e. it's both normalized and hashed
     */
    public function getPayload(): array
    {
        $payload = self::normalize($this->getMapping());
        Assert::isArray($payload);

        return $payload;
    }

    public function jsonSerialize(): array
    {
        return $this->getPayload();
    }

    /**
     * Returns the Meta/Facebook fields mapped to their respective values
     *
     * @return array<string, mixed>
     */
    abstract protected function getMapping(): array;

    /**
     * Returns a list of Meta/Facebook field names that must be normalized by \FacebookAds\Object\ServerSide\Normalizer::normalize
     *
     * @return list<string>
     */
    abstract protected static function getNormalizedFields(): array;

    /**
     * Returns a list of Meta/Facebook field names that must be hashed
     *
     * @return list<string>
     */
    protected static function getHashedFields(): array
    {
        return [];
    }

    /**
     * @param mixed $data
     *
     * @return array|string|float|int|null
     */
    private static function normalize($data, string $field = null)
    {
        if (null === $data) {
            return null;
        }

        if ($data instanceof \DateTimeInterface) {
            $data = $data->format('Ymd');
        }

        if (is_object($data) && method_exists($data, '__toString')) {
            $data = (string) $data;
        }

        if (is_string($data)) {
            Assert::notNull($field);
            if (in_array($field, static::getNormalizedFields(), true)) {
                $data = Normalizer::normalize($field, $data);
            }

            if (in_array($field, static::getHashedFields(), true)) {
                $data = Util::hash($data);
            }

            return $data;
        }

        if (is_int($data) || is_float($data)) {
            return $data;
        }

        Assert::isArray($data);

        /** @var mixed $datum */
        foreach ($data as $key => &$datum) {
            if ($datum instanceof self) {
                $datum = $datum->getPayload();
            } else {
                $datum = self::normalize($datum, is_string($key) ? $key : $field);
            }
        }
        unset($datum);

        // this will filter values we don't want to send to Meta/Facebook, i.e. nulls, empty strings, and empty arrays
        return array_filter($data, static function ($value): bool {
            return !(null === $value || '' === $value || [] === $value);
        });
    }
}
