<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

final class FbcGenerator implements FbcGeneratorInterface
{
    /**
     * See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc/#fbc
     */
    public function generate(string $facebookClickId): string
    {
        /**
         * creationTime is the UNIX time since epoch in milliseconds when the _fbc cookie was saved
         */
        $creationTime = ceil(microtime(true) * 1000);

        return sprintf('fb.1.%s.%s', $creationTime, $facebookClickId);
    }
}
