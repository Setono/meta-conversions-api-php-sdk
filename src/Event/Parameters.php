<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use FacebookAds\Object\ServerSide\Normalizer;
use FacebookAds\Object\ServerSide\Util;
use Webmozart\Assert\Assert;

abstract class Parameters
{
    public const PAYLOAD_CONTEXT_BROWSER = 'browser';

    public const PAYLOAD_CONTEXT_SERVER = 'server';

    /**
     * This method returns an array representation of the object ready
     * to be sent to Meta/Facebook, i.e. it's both normalized and hashed
     *
     * @return array<string, mixed>
     */
    public function getPayload(string $context = self::PAYLOAD_CONTEXT_SERVER): array
    {
        // The mapping keys are field names (strings), so iterating here lets the return type stay the precise
        // array<string, mixed> that consumers like FbqGenerator::generateInit() rely on. The recursive normalize()
        // below works on values of unknown key type, hence it can only yield array<array-key, mixed>.
        $payload = [];
        foreach ($this->getMapping($context) as $field => $value) {
            $payload[$field] = $value instanceof self ? $value->getPayload() : self::normalize($value, $field);
        }

        return self::filterEmptyValues($payload);
    }

    /**
     * Returns the Meta/Facebook fields mapped to their respective values
     *
     * @return array<string, mixed>
     */
    abstract protected function getMapping(string $context): array;

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
     * @return array<array-key, mixed>|string|float|int|bool|null
     */
    private static function normalize($data, ?string $field = null)
    {
        if (null === $data) {
            return null;
        }

        if ($data instanceof \DateTimeInterface) {
            $data = $data->format('Ymd');
        }

        if ($data instanceof \Stringable) {
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

        if (is_int($data) || is_float($data) || is_bool($data)) {
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

        return self::filterEmptyValues($data);
    }

    /**
     * Filters out the values we don't want to send to Meta/Facebook, i.e. nulls, empty strings, and empty arrays
     *
     * @template TKey of array-key
     *
     * @param array<TKey, mixed> $data
     *
     * @return array<TKey, mixed>
     */
    private static function filterEmptyValues(array $data): array
    {
        return array_filter($data, static function ($value): bool {
            return !(null === $value || '' === $value || [] === $value);
        });
    }
}
