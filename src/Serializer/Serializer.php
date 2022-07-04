<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Serializer;

use Setono\MetaConversionsApi\Event\Parameters;

final class Serializer implements SerializerInterface
{
    public function serialize($parameters): string
    {
        if (is_array($parameters)) {
            $data = array_map(static function (Parameters $innerParameters): array {
                return $innerParameters->normalize();
            }, $parameters);
        } else {
            $data = $parameters->normalize();

            if ([] === $data) {
                return '{}';
            }
        }

        return json_encode($data, \JSON_THROW_ON_ERROR);
    }
}
