<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi;

use Psr\Log\AbstractLogger;

final class TestLogger extends AbstractLogger
{
    /** @var list<non-empty-string> */
    public array $messages = [];

    /**
     * @param mixed $level
     * @param string|\Stringable $message
     * @param array<array-key, mixed> $context
     */
    public function log($level, $message, array $context = []): void
    {
        $message = (string) $message;
        if ('' === $message) {
            return;
        }

        $this->messages[] = $message;
    }

    /**
     * @param non-empty-string $regexp
     */
    public function hasMessageMatching(string $regexp): bool
    {
        foreach ($this->messages as $message) {
            if (preg_match($regexp, $message) === 1) {
                return true;
            }
        }

        return false;
    }
}
