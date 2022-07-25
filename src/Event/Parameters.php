<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use FacebookAds\Object\ServerSide\Normalizer;
use FacebookAds\Object\ServerSide\Util;
use JsonSerializable;

abstract class Parameters implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        return $this->normalize();
    }

    public function normalize(): array
    {
        return self::normalizeData($this->getMapping());
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

    private static function normalizeData(array $data): array
    {
        /** @var mixed $datum */
        foreach ($data as $field => &$datum) {
            if ($datum instanceof \DateTimeInterface) {
                $datum = $datum->format('Ymd');
            } elseif ($datum instanceof self) {
                $datum = $datum->normalize();
            } elseif (is_array($datum)) {
                $datum = self::normalizeData($datum);
            } elseif (is_string($field) && is_string($datum) && in_array($field, static::getNormalizedFields(), true)) {
                $datum = Normalizer::normalize($field, $datum);
            } elseif (is_object($datum) && method_exists($datum, '__toString')) {
                $datum = (string) $datum;
            }

            if (in_array($field, self::getHashedFields(), true)) {
                $datum = self::hash($datum);
            }
        }
        unset($datum);

        return array_filter($data, static function ($value) {
            return !(null === $value || '' === $value || [] === $value);
        });
    }

    /**
     * Returns a list of Meta/Facebook field names that must be hashed
     *
     * @return list<string>
     */
    private static function getHashedFields(): array
    {
        return [
            'em',
            'ph',
            'fn',
            'ln',
            'ge',
            'db',
            'ct',
            'st',
            'zp',
        ];
    }

    /**
     * @param mixed $value
     *
     * @return string|list<string>|null
     *
     * @see Util::hash()
     */
    private static function hash($value)
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            return hash('sha256', $value, false);
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map(static function ($item): ?string {
                if (!is_string($item)) {
                    return null;
                }

                return hash('sha256', $item, false);
            }, $value)));
        }

        throw new \RuntimeException(sprintf('Unexpected type of $value. Expecting null|string|list<string>, got %s', gettype($value)));
    }
}
