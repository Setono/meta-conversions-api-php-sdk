<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Serializer;

use Setono\MetaConversionsApi\Event\Parameters;

final class Serializer implements SerializerInterface
{
    public function serialize(Parameters $parameters): string
    {
        $data = array_filter($parameters->normalize(), static function ($value): bool {
            return !(null === $value || '' === $value || [] === $value);
        });

        return json_encode($data, \JSON_THROW_ON_ERROR | \JSON_FORCE_OBJECT);
    }
}
