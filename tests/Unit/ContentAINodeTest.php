<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Content\ContentEngine;
use Token27\NexusAI\Content\Node\ContentAINode;
use Token27\NexusAI\Content\Tests\Unit\Fixture\ArrayPromptRegistry;
use Token27\NexusAI\Driver\Fake\FakeDriver;
use Token27\NexusAI\Enum\FinishReason;
use Token27\NexusAI\Response\TextResponse;
use Token27\NexusAI\Tracking\Engine\TrackingEngine;
use Token27\NexusAI\Tracking\Store\InMemoryExecutionStore;
use Token27\NexusAI\Workflows\Engine\WorkflowBuilder;

final class ContentAINodeTest extends TestCase
{
    public function test_resolves_versioned_prompt_calls_driver_and_tracks_event(): void
    {
        $fakeDriver = new FakeDriver();
        $fakeDriver->willReturn(new TextResponse(
            text: 'Generated article text',
            finishReason: FinishReason::Stop,
        ));

        $promptRegistry = new ArrayPromptRegistry();
        $promptRegistry->add('article/research', '1.0.0', 'en', 'You are a researcher.', 'Write about {{topic}}.');

        $trackingStore = new InMemoryExecutionStore();

        $workflow = WorkflowBuilder::named('article-default')
            ->addNode('research', new ContentAINode(
                promptIdentifier: 'article/research',
                outputKey: 'content',
                promptVersion: '1.0.0',
            ))
            ->build();

        $engine = ContentEngine::create()
            ->withPromptRegistry($promptRegistry)
            ->withTracking(TrackingEngine::withStore($trackingStore));
        $engine->getRegistry()->register('article', $workflow);

        $result = $engine
            ->for('openai', 'gpt-4o-mini')
            ->withContentType('article')
            ->withDriver($fakeDriver)
            ->withRunId('run-content-ai')
            ->withLanguage('en')
            ->withVariable('topic', 'workflow orchestration')
            ->generate();

        $this->assertSame('Generated article text', $result->text);
        $fakeDriver->assertRequestCount(1);
        $fakeDriver->assertPromptContains('Write about workflow orchestration.');

        $events = $trackingStore->findByRun('run-content-ai');
        $this->assertCount(1, $events);
        $this->assertSame('prompt.executed', $events[0]->eventType->value);
        $this->assertSame('article', $events[0]->data['content_type']);
        $this->assertSame('article/research', $events[0]->data['prompt']['identifier']);
        $this->assertSame('test', $events[0]->data['prompt']['source']);
    }

    public function test_raw_prompt_node_does_not_require_prompt_registry(): void
    {
        $fakeDriver = new FakeDriver();
        $fakeDriver->willReturn(new TextResponse(
            text: 'Raw prompt generated text',
            finishReason: FinishReason::Stop,
        ));

        $trackingStore = new InMemoryExecutionStore();

        $workflow = WorkflowBuilder::named('raw-draft')
            ->addNode('draft', ContentAINode::rawPrompt(
                prompt: 'Write a short note about {{topic}} for {{audience}}.',
                systemPrompt: 'You are testing raw prompts.',
                identifier: 'runtime/draft-experiment',
                outputKey: 'content',
                language: 'en',
                source: 'runtime',
                temperature: 0.1,
            ))
            ->build();

        $engine = ContentEngine::create()
            ->withTracking(TrackingEngine::withStore($trackingStore));
        $engine->getRegistry()->register('article', $workflow);

        $result = $engine
            ->for('openai', 'gpt-4o-mini')
            ->withContentType('article')
            ->withDriver($fakeDriver)
            ->withRunId('run-raw-content-ai')
            ->withVariable('topic', 'prompt iteration')
            ->withVariable('audience', 'editors')
            ->generate();

        $this->assertSame('Raw prompt generated text', $result->text);
        $fakeDriver->assertRequestCount(1);
        $fakeDriver->assertPromptContains('Write a short note about prompt iteration for editors.');

        $events = $trackingStore->findByRun('run-raw-content-ai');
        $this->assertCount(1, $events);
        $this->assertSame('runtime/draft-experiment', $events[0]->data['prompt']['identifier']);
        $this->assertSame('0.0.0', $events[0]->data['prompt']['version']);
        $this->assertSame('runtime', $events[0]->data['prompt']['source']);
    }
}
