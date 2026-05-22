<?php

declare(strict_types=1);

namespace Token27\NexusAI\Content\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Content\ContentRegistry;
use Token27\NexusAI\Content\Exception\ContentWorkflowNotFoundException;
use Token27\NexusAI\Workflows\Engine\WorkflowBuilder;

final class ContentRegistryTest extends TestCase
{
    public function test_registers_and_resolves_workflows_by_content_type_and_name(): void
    {
        $workflow = WorkflowBuilder::named('article-default')
            ->addActionNode('done', fn ($ctx) => $ctx)
            ->build();

        $registry = new ContentRegistry();
        $registry->register('Article', $workflow);

        $this->assertTrue($registry->has('article'));
        $this->assertTrue($registry->hasContentType('ARTICLE'));
        $this->assertSame($workflow, $registry->resolve('article'));
        $this->assertSame(['article'], $registry->listContentTypes());
        $this->assertSame(['default'], $registry->listWorkflowNames('article'));
    }

    public function test_throws_when_workflow_is_missing(): void
    {
        $this->expectException(ContentWorkflowNotFoundException::class);

        (new ContentRegistry())->resolve('article');
    }
}
