<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Generator;

final class FbpGenerator implements FbpGeneratorInterface
{
    /**
     * See https://developers.facebook.com/docs/marketing-api/conversions-api/parameters/fbp-and-fbc/#fbp
     */
    public function generate(string $random = null): string
    {
        /**
         * creationTime is the UNIX time since epoch in milliseconds when the _fbc cookie was saved
         */
        $creationTime = ceil(microtime(true) * 1000);

        if (null === $random) {
            $random = (string) random_int(1_000_000_000, 9_999_999_999);
        }

        return sprintf('fb.1.%s.%s', $creationTime, $random);
    }
}
