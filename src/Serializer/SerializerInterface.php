<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Serializer;

use Setono\MetaConversionsApi\Event\Parameters;

interface SerializerInterface
{
    /**
     * @param Parameters|list<Parameters> $parameters
     */
    public function serialize($parameters): string;
}
