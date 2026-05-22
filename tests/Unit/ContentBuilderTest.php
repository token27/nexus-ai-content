<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Content\ContentEngine;
use Token27\NexusAI\Content\Contract\ContentEngineInterface;
use Token27\NexusAI\Content\Contract\ExtensionInterface;
use Token27\NexusAI\Workflows\Engine\WorkflowBuilder;

final class ContentBuilderTest extends TestCase
{
    public function test_generates_content_result_from_registered_workflow(): void
    {
        $workflow = WorkflowBuilder::named('article-default')
            ->addActionNode('write', fn ($ctx) => $ctx->with('content', 'Hello ' . $ctx->get('topic')))
            ->build();

        $engine = ContentEngine::create();
        $engine->getRegistry()->register('article', $workflow);

        $result = $engine
            ->for('openai', 'gpt-4o-mini')
            ->withContentType('article')
            ->withRunId('run-1')
            ->withVariable('topic', 'world')
            ->generate();

        $this->assertSame('run-1', $result->runId);
        $this->assertSame('article', $result->contentType);
        $this->assertSame('Hello world', $result->text);
        $this->assertSame(0.0, $result->costUsd);
        $this->assertSame(0, $result->totalTokens);
    }

    public function test_registers_extension_and_uses_fluent_builder(): void
    {
        $extension = new class () implements ExtensionInterface {
            public function register(ContentEngineInterface $engine): void
            {
                $workflow = WorkflowBuilder::named('quick-note')
                    ->addActionNode('write', fn ($ctx) => $ctx->with('text', 'note:' . $ctx->get('topic')))
                    ->build();

                $engine->getRegistry()->register('note', $workflow, 'quick');
            }

            public function getContentTypes(): array
            {
                return ['note'];
            }

            public function getName(): string
            {
                return 'test-extension';
            }
        };

        $result = ContentEngine::create()
            ->registerExtension($extension)
            ->for('openai', 'gpt-4o-mini')
            ->withContentType('note', 'quick')
            ->withVariable('topic', 'architecture')
            ->generate();

        $this->assertSame('note:architecture', $result->text);
    }
}
