<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use FacebookAds\Object\ServerSide\Normalizer;

abstract class Parameters
{
    /**
     * Returns an array of normalized data where empty values (i.e. '', null, and []) are filtered
     *
     * @throws \InvalidArgumentException if any of the properties cannot be normalized to a correct format
     */
    public function normalizeAndFilter(): array
    {
        return array_filter($this->normalize(), static function ($value) {
            return !(null === $value || '' === $value || [] === $value);
        });
    }

    /**
     * Returns an array where the keys are named as Meta/Facebook names them
     *
     * @return array<string, mixed>
     */
    abstract protected function normalize(): array;

    /**
     * @param string|list<string>|null $value
     *
     * @return string|list<string>|null
     */
    public static function normalizeField(string $field, $value)
    {
        if (null === $value) {
            return null;
        }

        if (is_array($value)) {
            return array_map(static function ($item) use ($field) {
                return Normalizer::normalize($field, $item);
            }, $value);
        }

        return Normalizer::normalize($field, $value);
    }

    /**
     * @param string|list<string>|null $value
     *
     * @return string|list<string>|null
     */
    public static function hash($value)
    {
        if (null === $value) {
            return null;
        }

        if (is_array($value)) {
            return array_map(static function (string $item) {
                return hash('sha256', $item, false);
            }, $value);
        }

        return hash('sha256', $value, false);
    }
}
