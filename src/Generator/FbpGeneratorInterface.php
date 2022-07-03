<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

interface FbpGeneratorInterface
{
    /**
     * @param string $random there is a random part to the fbp and if you want to provide that yourself, you can do it here
     */
    public function generate(string $random = null): string;
}
