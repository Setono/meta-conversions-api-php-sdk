<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Serializer;

use Setono\MetaConversionsApi\Event\Parameters;

final class Serializer implements SerializerInterface
{
    public function serialize($parameters): string
    {
        if (is_array($parameters)) {
            $data = array_map(static function (Parameters $params): array {
                return array_filter($params->normalize(), static function ($value): bool {
                    return !(null === $value || '' === $value || [] === $value);
                });
            }, $parameters);
        } else {
            $data = array_filter($parameters->normalize(), static function ($value): bool {
                return !(null === $value || '' === $value || [] === $value);
            });

            if ([] === $data) {
                return '{}';
            }
        }

        return json_encode($data, \JSON_THROW_ON_ERROR);
    }
}
