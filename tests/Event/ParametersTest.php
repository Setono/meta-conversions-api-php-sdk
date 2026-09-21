<?php

declare(strict_types=1);

namespace Setono\MetaConversionsApi\Event;

use PHPUnit\Framework\TestCase;

final class ParametersTest extends TestCase
{
    /**
     * @test
     */
    public function it_uses_the_server_context_by_default(): void
    {
        self::assertSame(['context' => 'Server'], (new ContextAwareParameters())->getPayload());
    }

    /**
     * @test
     */
    public function it_passes_the_context_on_to_nested_parameters(): void
    {
        $parameters = new ContextAwareParameters();
        $parameters->child = new ContextAwareParameters();
        $parameters->child->child = new ContextAwareParameters();
        $parameters->children = [new ContextAwareParameters(), new ContextAwareParameters()];

        self::assertSame([
            'context' => 'Browser',
            'child' => [
                'context' => 'Browser',
                'child' => ['context' => 'Browser'],
            ],
            'children' => [
                ['context' => 'Browser'],
                ['context' => 'Browser'],
            ],
        ], $parameters->getPayload(PayloadContext::Browser));
    }
}

final class ContextAwareParameters extends Parameters
{
    public ?self $child = null;

    /** @var list<self> */
    public array $children = [];

    protected function getMapping(PayloadContext $context): array
    {
        return [
            'context' => $context->name,
            'child' => $this->child,
            'children' => $this->children,
        ];
    }

    protected static function getNormalizedFields(): array
    {
        return [];
    }
}
