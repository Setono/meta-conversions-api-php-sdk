<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

interface FbcGeneratorInterface
{
    /**
     * @param string $facebookClickId the query param (i.e. the x in ?fbclid=x)
     */
    public function generate(string $facebookClickId): string;
}
