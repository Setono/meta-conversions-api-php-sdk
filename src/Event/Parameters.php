<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

abstract class Parameters
{
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

    /**
     * This method must normalize the values of the object
     */
    abstract public function normalize(): array;
}
