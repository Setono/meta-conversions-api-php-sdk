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
            // from inspecting existing fbp values it seems that the random part is a number between 1,000,000,000 and 1,999,999,999
            $random = (string) random_int(1_000_000_000, 1_999_999_999);
        }

        return sprintf('fb.1.%s.%s', $creationTime, $random);
    }
}
