<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Serializer;

use Setono\MetaConversionsApi\Event\Parameters;

interface SerializerInterface
{
    public function serialize(Parameters $parameters): string;
}
